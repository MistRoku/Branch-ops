import { http, errorMessage } from './http';
import { formatPrice } from './format';

/**
 * POS terminal state. Product field names match the API shape:
 * `selling_price` for money and summed `stock_levels[].quantity` for
 * availability (there is no `price`, `stock_quantity` or `category`).
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

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },

        get tax() {
            return this.subtotal * 0.1;
        },

        get total() {
            return this.subtotal + this.tax;
        },

        get cartCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },

        formatPrice,

        async init() {
            await this.loadProducts();
        },

        async loadProducts() {
            this.loading = true;
            this.loadError = '';
            try {
                const { data } = await http.get('/api/v1/products', { params: { per_page: 100 } });
                const list = data?.data?.data ?? data?.data ?? [];
                this.products = list.map((p) => ({
                    ...p,
                    unitPrice: Number(p.selling_price) || 0,
                    stock: (p.stock_levels ?? []).reduce((sum, level) => sum + (Number(level.quantity) || 0), 0),
                }));
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
                this.checkoutError = `Only ${product.stock} × ${product.name} available.`;
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
                this.checkoutError = `Only ${product.stock} × ${product.name} available.`;
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

        async processCheckout(paymentMethod = 'cash') {
            if (this.cart.length === 0 || this.processing) {
                return;
            }

            this.processing = true;
            this.checkoutError = '';
            try {
                const { data } = await http.post('/api/v1/sales', {
                    ...(branchId ? { branch_id: branchId } : {}),
                    payment_method: paymentMethod,
                    items: this.cart.map((item) => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price,
                    })),
                });
                this.lastSaleId = data?.data?.id ?? 'N/A';
                this.lastSaleTotal = this.total;
                this.showSuccessModal = true;
            } catch (error) {
                this.checkoutError = errorMessage(error, 'Failed to complete sale. Please try again.');
            } finally {
                this.processing = false;
            }
        },

        clearCart() {
            this.cart = [];
            this.checkoutError = '';
        },
    };
}
