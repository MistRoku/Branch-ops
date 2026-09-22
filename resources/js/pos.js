import { http, errorMessage } from './http';
import { formatPrice } from './format';

const DISPLAY_KEY = 'branchops-pos-display';
const suspendKey = (branch) => `branchops-suspended-${branch ?? 'all'}`;

/**
 * POS terminal state. Prices honor active specials, quantities stay
 * approximate on purpose: exact branch stock is back-office only, the
 * terminal shows availability status instead of numbers.
 */
export function posTerminal({ branchId = null } = {}) {
    return {
        searchQuery: '',
        products: [],
        filteredProducts: [],
        cart: [],
        loading: true,
        loadError: '',
        checkoutError: '',
        processing: false,
        showSuccessModal: false,
        lastSaleId: '',
        lastSaleTotal: 0,
        lastSaleDbId: null,

        paymentMethod: 'cash',
        paymentReference: '',
        splitCash: '',
        splitCard: '',
        tendered: '',
        tip: 0,
        couponCode: '',
        couponDiscount: 0,
        couponError: '',
        couponApplied: '',
        specials: {},

        customerSearch: '',
        customerResults: [],
        customerSearching: false,
        selectedCustomer: null,
        showNewCustomer: false,
        newCustomerName: '',
        newCustomerPhone: '',
        customerError: '',

        fulfillment: 'pickup',
        deliveryAddress: '',
        deliveryFee: 0,

        quoteSaving: false,
        quoteNumber: '',
        quoteError: '',

        showHistory: false,
        showPay: false,
        showSuspended: false,
        suspended: [],
        pastSales: [],
        historyLoading: false,
        historyError: '',
        selectedSale: null,
        saleDetailLoading: false,
        refundType: 'full',
        refundAmount: '',
        refundReason: '',
        refundError: '',
        refunding: false,
        voidReason: '',
        voidError: '',
        voiding: false,

        compact: false,

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },

        get tax() {
            return this.cart.reduce((sum, item) => {
                const rate = Number(item.taxRate) || 0;
                return sum + item.price * item.quantity * (rate / 100);
            }, 0);
        },

        get discount() {
            return Math.min(this.couponDiscount, this.subtotal);
        },

        get deliveryFeeAmount() {
            return this.fulfillment === 'delivery' ? Number(this.deliveryFee || 0) : 0;
        },

        get total() {
            return Math.max(0, this.subtotal - this.discount + this.tax + Number(this.tip || 0) + this.deliveryFeeAmount);
        },

        get change() {
            const tendered = parseFloat(this.tendered);
            if (Number.isNaN(tendered)) {
                return 0;
            }
            return tendered - this.total;
        },

        get splitTotal() {
            return (Number(this.splitCash) || 0) + (Number(this.splitCard) || 0);
        },

        get splitBalanced() {
            return Math.abs(this.splitTotal - this.total) < 0.015;
        },

        get cartCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },

        formatPrice,

        availability(product) {
            if (product.stock <= 0) {
                return 'Out of stock';
            }
            if (product.stock <= (product.reorder ?? 10)) {
                return 'Low stock';
            }
            return 'In stock';
        },

        async init() {
            try {
                const saved = JSON.parse(localStorage.getItem(DISPLAY_KEY) ?? '{}');
                this.compact = !!saved.compact;
            } catch {
                // Default display stands.
            }
            this.loadSuspended();
            await this.loadProducts();
        },

        loadSuspended() {
            try {
                this.suspended = JSON.parse(localStorage.getItem(suspendKey(branchId)) ?? '[]');
            } catch {
                this.suspended = [];
            }
        },

        persistSuspended() {
            try {
                localStorage.setItem(suspendKey(branchId), JSON.stringify(this.suspended));
            } catch {
                // Parking is best-effort.
            }
        },

        openPay() {
            if (this.cart.length === 0) {
                return;
            }
            this.checkoutError = '';
            this.showPay = true;
        },

        closePay() {
            this.showPay = false;
        },

        suspendSale() {
            if (this.cart.length === 0) {
                return;
            }
            const count = this.cartCount;
            this.suspended.unshift({
                id: Date.now(),
                label: `${count} item${count === 1 ? '' : 's'} — ${formatPrice(this.total)}`,
                summary: this.cart.map((i) => `${i.quantity} x ${i.product_name}`).join(', ').slice(0, 120),
                savedAt: new Date().toLocaleString(),
                cart: this.cart,
                customer: this.selectedCustomer,
                fulfillment: this.fulfillment,
                deliveryAddress: this.deliveryAddress,
                deliveryFee: this.deliveryFee,
                tip: this.tip,
                couponCode: this.couponApplied,
                couponDiscount: this.couponDiscount,
            });
            this.persistSuspended();
            this.clearCart();
            this.clearCustomer();
        },

        resumeSuspended(id) {
            const parked = this.suspended.find((s) => s.id === id);
            if (!parked) {
                return;
            }
            if (this.cart.length > 0) {
                this.suspendSale();
            }
            this.cart = parked.cart ?? [];
            this.selectedCustomer = parked.customer ?? null;
            this.fulfillment = parked.fulfillment ?? 'pickup';
            this.deliveryAddress = parked.deliveryAddress ?? '';
            this.deliveryFee = parked.deliveryFee ?? 0;
            this.tip = parked.tip ?? 0;
            this.couponCode = parked.couponCode ?? '';
            this.couponDiscount = parked.couponDiscount ?? 0;
            this.couponApplied = parked.couponCode ?? '';
            this.suspended = this.suspended.filter((s) => s.id !== id);
            this.persistSuspended();
            this.showSuspended = false;
        },

        discardSuspended(id) {
            this.suspended = this.suspended.filter((s) => s.id !== id);
            this.persistSuspended();
        },

        saveDisplay() {
            try {
                localStorage.setItem(DISPLAY_KEY, JSON.stringify({ compact: this.compact }));
            } catch {
                // Display preference is best-effort.
            }
        },

        async loadProducts() {
            if (this._loadingPromise) {
                return this._loadingPromise;
            }
            this._loadingPromise = this._fetchProducts();
            try {
                return await this._loadingPromise;
            } finally {
                this._loadingPromise = null;
            }
        },

        async _fetchProducts() {
            this.loading = true;
            this.loadError = '';
            try {
                const [productsRes, specialsRes] = await Promise.all([
                    http.get('/api/v1/products', { params: { per_page: 100 } }),
                    http.get('/api/v1/specials/active', { params: branchId ? { branch_id: branchId } : {} }),
                ]);
                const data = productsRes.data;
                const list = data?.data?.data ?? data?.data ?? [];
                const specials = specialsRes.data?.data ?? {};
                this.specials = specials;
                this.products = list.map((p) => {
                    const special = specials[p.id];
                    const unitPrice = special ? Number(special.price) : Number(p.selling_price) || 0;
                    return {
                        ...p,
                        unitPrice,
                        onSpecial: !!special,
                        taxRate: Number(p.tax_rate) || 0,
                        reorder: Number(p.reorder_level) || 10,
                        uom: p.unit_of_measure ?? 'piece',
                        stock: (p.stock_levels ?? []).reduce((sum, level) => sum + (Number(level.quantity) || 0), 0),
                    };
                });
                this.filteredProducts = this.products;
            } catch (error) {
                this.loadError = errorMessage(error, 'Failed to load products.');
            } finally {
                this.loading = false;
            }
        },

        filterProducts() {
            const query = this.searchQuery.trim().toLowerCase();
            this.filteredProducts = this.products.filter((product) => {
                if (!query) {
                    return true;
                }

                return (
                    product.name?.toLowerCase().includes(query) ||
                    product.sku?.toLowerCase().includes(query) ||
                    product.barcode?.toLowerCase().includes(query)
                );
            });
        },

        addToCart(product) {
            if (product.stock <= 0) {
                return;
            }

            const existing = this.cart.find((item) => item.product_id === product.id);
            const inCart = existing?.quantity ?? 0;
            if (inCart + 1 > product.stock) {
                this.checkoutError = `Only a few ${product.name} left in stock.`;
                return;
            }

            this.checkoutError = '';
            if (existing) {
                existing.quantity += 1;
            } else {
                this.cart.push({
                    product_id: product.id,
                    product_name: product.name,
                    price: product.unitPrice,
                    taxRate: product.taxRate,
                    uom: product.uom,
                    quantity: 1,
                });
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        incrementQuantity(index) {
            const item = this.cart[index];
            const product = this.products.find((p) => p.id === item.product_id);
            if (product && item.quantity + 1 > product.stock) {
                this.checkoutError = `Only a few ${product.name} left in stock.`;
                return;
            }
            item.quantity += 1;
        },

        decrementQuantity(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity -= 1;
            } else {
                this.removeFromCart(index);
            }
        },

        async applyCoupon() {
            const code = this.couponCode.trim();
            if (!code) {
                return;
            }
            this.couponError = '';
            try {
                const { data } = await http.post('/api/v1/coupons/validate', {
                    code,
                    subtotal: this.subtotal,
                });
                this.couponDiscount = Number(data?.data?.discount) || 0;
                this.couponApplied = data?.data?.code ?? code;
            } catch (error) {
                this.couponDiscount = 0;
                this.couponApplied = '';
                this.couponError = errorMessage(error, 'Coupon code is not valid for this sale.');
            }
        },

        removeCoupon() {
            this.couponCode = '';
            this.couponDiscount = 0;
            this.couponApplied = '';
            this.couponError = '';
        },

        async searchCustomers() {
            const query = this.customerSearch.trim();
            if (query.length < 2) {
                this.customerResults = [];
                return;
            }
            this.customerSearching = true;
            try {
                const { data } = await http.get('/api/v1/customers', { params: { search: query } });
                this.customerResults = data?.data ?? [];
            } catch {
                this.customerResults = [];
            } finally {
                this.customerSearching = false;
            }
        },

        async selectCustomer(customer) {
            this.customerError = '';
            try {
                const { data } = await http.get(`/api/v1/customers/${customer.id}`);
                this.selectedCustomer = data?.data ?? customer;
            } catch {
                this.selectedCustomer = customer;
            }
            this.customerResults = [];
            this.customerSearch = '';
        },

        clearCustomer() {
            this.selectedCustomer = null;
        },

        async createCustomer() {
            if (!this.newCustomerName.trim()) {
                return;
            }
            this.customerError = '';
            try {
                const { data } = await http.post('/api/v1/customers', {
                    name: this.newCustomerName.trim(),
                    phone: this.newCustomerPhone.trim() || null,
                });
                this.selectedCustomer = data?.data ?? null;
                this.showNewCustomer = false;
                this.newCustomerName = '';
                this.newCustomerPhone = '';
            } catch (error) {
                this.customerError = errorMessage(error, 'Failed to add customer.');
            }
        },

        async saveQuote() {
            if (this.cart.length === 0 || this.quoteSaving) {
                return;
            }
            this.quoteSaving = true;
            this.quoteError = '';
            this.quoteNumber = '';
            try {
                const { data } = await http.post('/api/v1/quotes', {
                    ...(branchId ? { branch_id: branchId } : {}),
                    ...(this.selectedCustomer ? { customer_id: this.selectedCustomer.id } : {}),
                    discount_amount: this.discount,
                    items: this.cart.map((item) => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price,
                    })),
                });
                this.quoteNumber = data?.data?.quote_number ?? '';
            } catch (error) {
                this.quoteError = errorMessage(error, 'Failed to save quotation.');
            } finally {
                this.quoteSaving = false;
            }
        },

        async processCheckout() {
            if (this.cart.length === 0 || this.processing) {
                return;
            }
            if (this.paymentMethod === 'split' && !this.splitBalanced) {
                this.checkoutError = 'Split amounts must add up to the sale total.';
                return;
            }
            if (this.fulfillment === 'delivery' && !this.deliveryAddress.trim()) {
                this.checkoutError = 'A delivery address is required for delivery orders.';
                return;
            }

            this.processing = true;
            this.checkoutError = '';
            try {
                const payload = {
                    ...(branchId ? { branch_id: branchId } : {}),
                    payment_method: this.paymentMethod,
                    fulfillment: this.fulfillment,
                    items: this.cart.map((item) => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price,
                    })),
                };
                if (this.selectedCustomer) {
                    payload.customer_id = this.selectedCustomer.id;
                }
                if (this.paymentMethod === 'split') {
                    payload.payments = [
                        { method: 'cash', amount: Number(this.splitCash) || 0 },
                        { method: 'card', amount: Number(this.splitCard) || 0 },
                    ].filter((p) => p.amount > 0);
                    payload.tendered_amount = this.splitTotal;
                } else {
                    if (this.paymentReference.trim()) {
                        payload.payment_reference = this.paymentReference.trim();
                    }
                    if (this.paymentMethod === 'cash' && this.tendered !== '') {
                        payload.tendered_amount = Number(this.tendered);
                    }
                }
                if (Number(this.tip) > 0) {
                    payload.tip_amount = Number(this.tip);
                }
                if (this.couponApplied) {
                    payload.coupon_code = this.couponApplied;
                }
                if (this.fulfillment === 'delivery') {
                    payload.delivery_address = this.deliveryAddress.trim();
                    payload.delivery_fee = Number(this.deliveryFee) || 0;
                }
                const { data } = await http.post('/api/v1/sales', payload);
                this.lastSaleId = data?.data?.invoice_number ?? data?.data?.id ?? 'N/A';
                this.lastSaleDbId = data?.data?.id ?? null;
                this.lastSaleTotal = Number(data?.data?.total_amount) || this.total;
                this.showPay = false;
                this.showSuccessModal = true;
            } catch (error) {
                this.checkoutError = errorMessage(error, 'Failed to complete sale. Please try again.');
            } finally {
                this.processing = false;
            }
        },

        printReceipt() {
            if (this.lastSaleDbId) {
                window.open(`/admin/sales/${this.lastSaleDbId}/receipt`, '_blank');
            }
        },

        printReceiptFor(id) {
            window.open(`/admin/sales/${id}/receipt`, '_blank');
        },

        clearCart() {
            this.cart = [];
            this.checkoutError = '';
            this.tendered = '';
            this.splitCash = '';
            this.splitCard = '';
            this.tip = 0;
            this.paymentReference = '';
            this.quoteNumber = '';
            this.quoteError = '';
            this.removeCoupon();
        },

        async openHistory() {
            this.showHistory = true;
            await this.loadSales();
        },

        async loadSales() {
            this.historyLoading = true;
            this.historyError = '';
            try {
                const { data } = await http.get('/api/v1/sales', { params: { per_page: 20 } });
                this.pastSales = data?.data?.data ?? data?.data ?? [];
            } catch (error) {
                this.historyError = errorMessage(error, 'Failed to load past sales.');
            } finally {
                this.historyLoading = false;
            }
        },

        async viewSale(id) {
            this.saleDetailLoading = true;
            this.refundError = '';
            this.voidError = '';
            try {
                const { data } = await http.get(`/api/v1/sales/${id}`);
                this.selectedSale = data?.data ?? null;
            } catch (error) {
                this.historyError = errorMessage(error, 'Failed to load sale.');
            } finally {
                this.saleDetailLoading = false;
            }
        },

        closeSaleDetail() {
            this.selectedSale = null;
            this.refundAmount = '';
            this.refundReason = '';
            this.refundError = '';
            this.voidReason = '';
            this.voidError = '';
        },

        async submitRefund() {
            if (!this.selectedSale || this.refunding) {
                return;
            }
            this.refunding = true;
            this.refundError = '';
            try {
                const payload = { type: this.refundType, reason: this.refundReason };
                if (this.refundType === 'partial') {
                    payload.amount = Number(this.refundAmount);
                }
                await http.post(`/api/v1/sales/${this.selectedSale.id}/refund`, payload);
                await this.viewSale(this.selectedSale.id);
                await this.loadSales();
                await this.loadProducts();
            } catch (error) {
                this.refundError = errorMessage(error, 'Failed to record refund.');
            } finally {
                this.refunding = false;
            }
        },

        async submitVoid() {
            if (!this.selectedSale || this.voiding) {
                return;
            }
            this.voiding = true;
            this.voidError = '';
            try {
                await http.post(`/api/v1/sales/${this.selectedSale.id}/void`, { reason: this.voidReason });
                await this.viewSale(this.selectedSale.id);
                await this.loadSales();
                await this.loadProducts();
            } catch (error) {
                this.voidError = errorMessage(error, 'Failed to void sale.');
            } finally {
                this.voiding = false;
            }
        },
    };
}
