@extends('components.layouts.pos')

@section('title', 'POS Terminal - BranchOps')

@section('content')
<div class="h-full flex" :class="compact ? 'pos-compact' : ''" x-data="posTerminal({ branchId: {{ auth()->user()->branch_id ?? 'null' }} })">
    <!-- Left Panel - Product Grid -->
    <div class="flex-1 overflow-hidden flex flex-col">
        <!-- Search + actions -->
        <div class="p-4 border-b border-brand-200 bg-brand-100">
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <label for="pos-search" class="sr-only">Search products</label>
                    <input id="pos-search"
                           type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="filterProducts()"
                           placeholder="Search products by name, SKU or barcode..."
                           autocomplete="off"
                           class="w-full pl-10 pr-4 py-2 border border-brand-300 bg-brand-50 text-sm text-brand-900">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <button @click="loadProducts()" class="px-3 py-2 border border-brand-300 bg-brand-50 text-sm font-medium text-brand-700">Reload</button>
                <button @click="openHistory()" class="px-3 py-2 border border-brand-300 bg-brand-50 text-sm font-medium text-brand-700">Sales history</button>
                <button @click="compact = !compact; saveDisplay()" class="px-3 py-2 border border-brand-300 bg-brand-50 text-xs font-medium text-brand-700" x-text="compact ? 'Comfort view' : 'Compact view'"></button>
            </div>
            <p x-show="loadError" class="mt-2 text-sm text-danger flex items-center gap-3" role="alert">
                <span x-text="loadError"></span>
                <button @click="loadProducts()" class="underline font-medium">Try again</button>
            </p>
        </div>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-4 bg-brand-50">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <template x-if="loading">
                    <template x-for="i in 8" :key="i">
                        <div class="bg-brand-100 border border-brand-200 p-4" aria-hidden="true" role="status" aria-label="Loading product">
                            <div class="h-24 bg-brand-200 mb-3 animate-pulse"></div>
                            <div class="h-4 bg-brand-200 w-3/4 mb-2 animate-pulse"></div>
                            <div class="h-3 bg-brand-200 w-1/2 animate-pulse"></div>
                        </div>
                    </template>
                </template>
                <template x-if="!loading && filteredProducts.length === 0">
                    <div class="col-span-full text-center py-12 bg-brand-100 border border-brand-200">
                        <p class="text-sm text-brand-600" x-text="products.length === 0 ? 'No products available. Use Reload to fetch stock.' : 'No products match your search'"></p>
                    </div>
                </template>
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="addToCart(product)"
                            :disabled="product.stock <= 0"
                            class="bg-brand-100 border border-brand-200 p-4 text-left disabled:opacity-50 disabled:cursor-not-allowed">
                        <div class="flex items-center justify-between mb-2">
                            <span x-show="product.onSpecial" class="text-xs px-2 py-0.5 bg-warning text-white">Special</span>
                            <span x-show="!product.onSpecial" class="text-xs text-brand-400" x-text="product.uom"></span>
                        </div>
                        <h3 class="font-medium text-brand-900 text-sm mb-1" x-text="product.name"></h3>
                        <p class="text-xs text-brand-500 mb-2"><span x-text="product.sku"></span> · <span x-text="product.uom"></span></p>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-accent-600" x-text="formatPrice(product.unitPrice)"></span>
                            <span class="text-xs px-2 py-1 text-white"
                                  :class="availability(product) === 'In stock' ? 'bg-success' : (availability(product) === 'Low stock' ? 'bg-warning' : 'bg-danger')"
                                  x-text="availability(product)"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Right Panel - Cart -->
    <div class="w-96 bg-brand-100 border-l border-brand-200 flex flex-col">
        <div class="p-4 border-b border-brand-200">
            <h2 class="font-semibold text-brand-900">Current Sale</h2>
            <p class="text-xs text-brand-600 mt-1" x-text="cartCount + (cartCount === 1 ? ' item' : ' items')"></p>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            <template x-if="cart.length === 0">
                <div class="text-center py-12 text-brand-500 border border-brand-200 bg-brand-50">
                    <p class="text-sm">Cart is empty</p>
                    <p class="text-xs mt-1">Select products to add them</p>
                </div>
            </template>
            <template x-if="cart.length > 0">
                <div class="space-y-3">
                    <template x-for="(item, index) in cart" :key="item.product_id">
                        <div class="border border-brand-200 bg-brand-50 p-3">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm text-brand-900" x-text="item.product_name"></h4>
                                    <p class="text-xs text-brand-600"><span x-text="formatPrice(item.price)"></span> per <span x-text="item.uom"></span></p>
                                </div>
                                <button @click="removeFromCart(index)" :aria-label="'Remove ' + item.product_name" class="text-brand-400 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="decrementQuantity(index)" :aria-label="'Decrease quantity of ' + item.product_name" class="w-8 h-8 flex items-center justify-center border border-brand-300 bg-brand-100">-</button>
                                <span class="flex-1 text-center text-sm font-medium" x-text="item.quantity + ' ' + item.uom"></span>
                                <button @click="incrementQuantity(index)" :aria-label="'Increase quantity of ' + item.product_name" class="w-8 h-8 flex items-center justify-center border border-brand-300 bg-brand-100">+</button>
                            </div>
                            <div class="mt-2 pt-2 border-t border-brand-200 flex justify-between items-center">
                                <span class="text-xs text-brand-600">Subtotal</span>
                                <span class="font-semibold text-sm" x-text="formatPrice(item.price * item.quantity)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Payment + totals -->
        <div class="p-4 border-t border-brand-200 bg-brand-50">
            <p x-show="checkoutError" x-text="checkoutError" class="mb-3 text-sm text-danger" role="alert"></p>
            <div class="mb-3 border border-brand-200 bg-brand-100 p-2">
                <p class="text-xs font-medium text-brand-700 mb-1">Customer (keeps them coming back)</p>
                <template x-if="!selectedCustomer">
                    <div>
                        <input x-model="customerSearch" @input.debounce.300ms="searchCustomers()" placeholder="Search name or phone..." class="w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-50" />
                        <template x-if="customerSearching"><p class="text-xs text-brand-500 mt-1">Searching...</p></template>
                        <template x-for="c in customerResults" :key="c.id">
                            <button @click="selectCustomer(c)" class="w-full text-left text-xs px-2 py-1 border-b border-brand-100">
                                <span class="font-medium" x-text="c.name"></span>
                                <span class="text-brand-500" x-text="c.phone ?? ''"></span>
                            </button>
                        </template>
                        <button @click="showNewCustomer = !showNewCustomer" class="text-xs underline mt-1">New customer</button>
                        <div x-show="showNewCustomer" class="mt-1 space-y-1">
                            <input x-model="newCustomerName" placeholder="Full name" class="w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-50" />
                            <input x-model="newCustomerPhone" placeholder="Phone" class="w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-50" />
                            <p x-show="customerError" x-text="customerError" class="text-xs text-danger"></p>
                            <button @click="createCustomer()" class="px-3 py-1 text-xs bg-brand-900 text-white font-medium">Add customer</button>
                        </div>
                    </div>
                </template>
                <template x-if="selectedCustomer">
                    <p class="text-xs flex items-center justify-between">
                        <span><span class="font-medium" x-text="selectedCustomer.name"></span>
                        <span class="text-brand-500" x-text="' · ' + (selectedCustomer.sales_count ?? 0) + ' past sales · ' + (selectedCustomer.loyalty_points ?? 0) + ' pts'"></span></span>
                        <button @click="clearCustomer()" class="underline">Remove</button>
                    </p>
                </template>
            </div>
            <div class="mb-3">
                <p class="text-xs font-medium text-brand-700 mb-1">Payment method</p>
                <div class="flex gap-1">
                    <template x-for="method in ['cash','card','split']" :key="method">
                        <button @click="paymentMethod = method" class="flex-1 px-2 py-1.5 text-xs font-medium border"
                                :class="paymentMethod === method ? 'themed-bg border-transparent' : 'border-brand-300 bg-brand-100 text-brand-700'"
                                x-text="method.charAt(0).toUpperCase() + method.slice(1)"></button>
                    </template>
                </div>
                <div x-show="paymentMethod === 'card'" class="mt-2">
                    <label class="text-xs text-brand-700">Card reference<input x-model="paymentReference" placeholder="Card slip reference" class="mt-1 w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" /></label>
                </div>
                <div x-show="paymentMethod === 'split'" class="mt-2 flex gap-2">
                    <label class="flex-1 text-xs text-brand-700">Cash (R)<input x-model="splitCash" type="number" min="0" step="0.01" class="mt-1 w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" /></label>
                    <label class="flex-1 text-xs text-brand-700">Card (R)<input x-model="splitCard" type="number" min="0" step="0.01" class="mt-1 w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" /></label>
                </div>
                <p x-show="paymentMethod === 'split'" class="text-xs mt-1" :class="splitBalanced ? 'text-success' : 'text-danger'" x-text="'Split total R ' + splitTotal.toFixed(2) + (splitBalanced ? ' (balanced)' : ' (must equal total)')"></p>
            </div>
            <div class="mb-3">
                <p class="text-xs font-medium text-brand-700 mb-1">Fulfilment</p>
                <div class="flex gap-1 mb-2">
                    <template x-for="option in ['pickup','delivery']" :key="option">
                        <button @click="fulfillment = option" class="flex-1 px-2 py-1.5 text-xs font-medium border"
                                :class="fulfillment === option ? 'themed-bg border-transparent' : 'border-brand-300 bg-brand-100 text-brand-700'"
                                x-text="option.charAt(0).toUpperCase() + option.slice(1)"></button>
                    </template>
                </div>
                <div x-show="fulfillment === 'delivery'" class="flex gap-2">
                    <input x-model="deliveryAddress" placeholder="Delivery address" class="flex-1 px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" />
                    <input x-model.number="deliveryFee" type="number" min="0" step="0.01" placeholder="Fee" class="w-20 px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" />
                </div>
            </div>
            <div class="flex gap-2 mb-3">
                <label class="flex-1 text-xs text-brand-700">Tip (R)<input x-model.number="tip" type="number" min="0" step="0.01" class="mt-1 w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" /></label>
                <label x-show="paymentMethod === 'cash'" class="flex-1 text-xs text-brand-700">Tendered (R)<input x-model="tendered" type="number" min="0" step="0.01" class="mt-1 w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" /></label>
            </div>
            <div class="mb-3">
                <template x-if="!couponApplied">
                    <div class="flex gap-2">
                        <input x-model="couponCode" placeholder="Coupon code" class="flex-1 px-2 py-1.5 text-sm border border-brand-300 bg-brand-100" />
                        <button @click="applyCoupon()" class="px-3 py-1.5 text-sm border border-brand-300 bg-brand-100 font-medium">Apply</button>
                    </div>
                </template>
                <p x-show="couponError" x-text="couponError" class="text-xs text-danger mt-1"></p>
                <p x-show="couponApplied" class="text-xs text-success mt-1 flex items-center justify-between">
                    <span>Coupon <span x-text="couponApplied"></span>: -<span x-text="formatPrice(couponDiscount)"></span></span>
                    <button @click="removeCoupon()" class="underline">Remove</button>
                </p>
            </div>
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex justify-between"><span class="text-brand-600">Subtotal</span><span class="font-medium" x-text="formatPrice(subtotal)"></span></div>
                <div class="flex justify-between"><span class="text-brand-600">Discount</span><span class="font-medium" x-text="formatPrice(discount)"></span></div>
                <div class="flex justify-between"><span class="text-brand-600">Tax</span><span class="font-medium" x-text="formatPrice(tax)"></span></div>
                <div class="flex justify-between"><span class="text-brand-600">Tip</span><span class="font-medium" x-text="formatPrice(Number(tip || 0))"></span></div>
                <div x-show="fulfillment === 'delivery'" class="flex justify-between"><span class="text-brand-600">Delivery fee</span><span class="font-medium" x-text="formatPrice(deliveryFeeAmount)"></span></div>
                <div class="flex justify-between text-lg font-semibold pt-2 border-t border-brand-200">
                    <span class="text-brand-900">Total</span>
                    <span class="text-accent-600" x-text="formatPrice(total)"></span>
                </div>
                <div x-show="paymentMethod === 'cash' && tendered !== ''" class="flex justify-between">
                    <span class="text-brand-600">Change</span><span class="font-medium" x-text="formatPrice(Math.max(0, change))"></span>
                </div>
            </div>
            <button @click="processCheckout()" :disabled="cart.length === 0 || processing"
                    class="themed-bg w-full py-3 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!processing">Complete Sale</span>
                <span x-show="processing" x-cloak>Processing...</span>
            </button>
            <button @click="saveQuote()" :disabled="cart.length === 0 || quoteSaving"
                    class="w-full py-2 mt-2 text-sm border border-brand-300 bg-brand-100 font-medium disabled:opacity-50">
                <span x-show="!quoteSaving">Save as quotation</span>
                <span x-show="quoteSaving" x-cloak>Saving...</span>
            </button>
            <p x-show="quoteNumber" class="text-xs text-success mt-1">Quotation <span x-text="quoteNumber"></span> saved. Print the proforma from Quotations in back office.</p>
            <p x-show="quoteError" x-text="quoteError" class="text-xs text-danger mt-1"></p>
        </div>
    </div>

    <!-- Past sales panel -->
    <div x-show="showHistory" x-cloak class="fixed inset-y-0 right-0 w-full max-w-md bg-brand-100 border-l border-brand-300 z-40 flex flex-col" role="dialog" aria-label="Past sales">
        <div class="p-4 border-b border-brand-200 flex items-center justify-between">
            <h2 class="font-semibold text-brand-900">Past Sales</h2>
            <button @click="showHistory = false; closeSaleDetail()" class="text-sm underline">Close</button>
        </div>
        <template x-if="!selectedSale">
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <p x-show="historyError" x-text="historyError" class="text-sm text-danger"></p>
                <template x-if="historyLoading"><p class="text-sm text-brand-600">Loading sales...</p></template>
                <template x-for="sale in pastSales" :key="sale.id">
                    <button @click="viewSale(sale.id)" class="w-full text-left border border-brand-200 bg-brand-50 p-3">
                        <div class="flex justify-between text-sm"><span class="font-medium" x-text="sale.invoice_number"></span><span class="font-semibold" x-text="formatPrice(sale.total_amount)"></span></div>
                        <div class="flex justify-between text-xs text-brand-600 mt-1"><span x-text="sale.payment_method + ' · ' + sale.status"></span><span x-text="sale.created_at"></span></div>
                    </button>
                </template>
                <template x-if="!historyLoading && pastSales.length === 0"><p class="text-sm text-brand-600 text-center py-8">No past sales found</p></template>
            </div>
        </template>
        <template x-if="selectedSale">
            <div class="flex-1 overflow-y-auto p-4">
                <button @click="closeSaleDetail()" class="text-sm underline mb-3">Back to list</button>
                <template x-if="saleDetailLoading"><p class="text-sm text-brand-600">Loading sale...</p></template>
                <template x-if="!saleDetailLoading && selectedSale">
                    <div>
                        <h3 class="font-semibold" x-text="selectedSale.invoice_number"></h3>
                        <p class="text-xs text-brand-600 mb-2"><span x-text="selectedSale.status"></span> · <span x-text="formatPrice(selectedSale.total_amount)"></span></p>
                        <div class="flex gap-2 mb-3">
                            <button @click="printReceiptFor(selectedSale.id)" class="px-3 py-1.5 text-xs border border-brand-300 bg-brand-50 font-medium">Print receipt</button>
                        </div>
                        <div class="border border-brand-200 bg-brand-50 p-3 mb-3">
                            <p class="text-xs font-medium mb-2">Refund this sale</p>
                            <div class="flex gap-2 mb-2 text-xs">
                                <label><input type="radio" value="full" x-model="refundType" /> Full</label>
                                <label><input type="radio" value="partial" x-model="refundType" /> Partial</label>
                            </div>
                            <input x-show="refundType === 'partial'" x-model="refundAmount" type="number" min="0.01" step="0.01" placeholder="Amount" class="w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100 mb-2" />
                            <input x-model="refundReason" placeholder="Reason (required)" class="w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100 mb-2" />
                            <p x-show="refundError" x-text="refundError" class="text-xs text-danger mb-2"></p>
                            <button @click="submitRefund()" :disabled="refunding" class="px-3 py-1.5 text-xs bg-brand-900 text-white font-medium disabled:opacity-50">Record refund</button>
                        </div>
                        <div class="border border-brand-200 bg-brand-50 p-3">
                            <p class="text-xs font-medium mb-2">Void this sale (managers only)</p>
                            <input x-model="voidReason" placeholder="Void reason (required)" class="w-full px-2 py-1.5 text-sm border border-brand-300 bg-brand-100 mb-2" />
                            <p x-show="voidError" x-text="voidError" class="text-xs text-danger mb-2"></p>
                            <button @click="submitVoid()" :disabled="voiding" class="px-3 py-1.5 text-xs bg-danger text-white font-medium disabled:opacity-50">Void sale</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Checkout Success Modal -->
    <div x-show="showSuccessModal" x-cloak
         @keydown.escape.window="showSuccessModal = false; clearCart()"
         role="dialog" aria-modal="true" aria-label="Sale completed"
         class="fixed inset-0 bg-brand-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-brand-100 border border-brand-200 p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-brand-900 mb-2">Sale Completed</h3>
                <p class="text-sm text-brand-600 mb-2">Invoice: <span x-text="lastSaleId"></span></p>
                <p class="text-sm text-brand-600 mb-6">Total: <span class="font-semibold" x-text="formatPrice(lastSaleTotal)"></span></p>
                <div class="flex gap-2">
                    <button @click="printReceipt()" class="flex-1 py-2 border border-brand-300 bg-brand-50 font-medium">Print receipt</button>
                    <button @click="showSuccessModal = false; clearCart()" class="themed-bg flex-1 py-2 font-medium">New Sale</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
