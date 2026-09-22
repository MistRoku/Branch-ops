@extends('components.layouts.pos')

@section('title', 'POS Terminal - BranchOps')

@section('content')
<div class="h-full flex" x-data="posTerminal({ branchId: {{ auth()->user()->branch_id ?? 'null' }} })">
    <!-- Left Panel - Product Grid -->
    <div class="flex-1 overflow-hidden flex flex-col">
        <!-- Search -->
        <div class="p-4 border-b border-brand-200 bg-brand-100">
            <div class="flex gap-3">
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
                <button @click="loadProducts()" class="px-4 py-2 border border-brand-300 bg-brand-50 text-sm font-medium text-brand-700">
                    Reload
                </button>
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
                        <svg class="w-10 h-10 mx-auto mb-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <p class="text-sm text-brand-600" x-text="products.length === 0 ? 'No products available. Use Reload to fetch stock.' : 'No products match your search'"></p>
                        <button @click="loadProducts()" class="mt-3 px-4 py-2 border border-brand-300 bg-brand-50 text-sm font-medium text-brand-700">
                            Reload products
                        </button>
                    </div>
                </template>
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="addToCart(product)"
                            :disabled="product.stock <= 0"
                            class="bg-brand-100 border border-brand-200 p-4 text-left disabled:opacity-50 disabled:cursor-not-allowed">
                        <div class="h-24 bg-brand-200 mb-3 flex items-center justify-center">
                            <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium text-brand-900 text-sm mb-1" x-text="product.name"></h3>
                        <p class="text-xs text-brand-500 mb-2" x-text="product.sku"></p>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-accent-600" x-text="formatPrice(product.unitPrice)"></span>
                            <span class="text-xs px-2 py-1 text-white"
                                  :class="product.stock > 10 ? 'bg-success' : (product.stock > 0 ? 'bg-warning' : 'bg-danger')"
                                  x-text="product.stock > 0 ? product.stock + ' left' : 'Out of stock'"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Right Panel - Cart -->
    <div class="w-96 bg-brand-100 border-l border-brand-200 flex flex-col">
        <!-- Cart Header -->
        <div class="p-4 border-b border-brand-200">
            <h2 class="font-semibold text-brand-900">Current Sale</h2>
            <p class="text-xs text-brand-600 mt-1" x-text="cartCount + (cartCount === 1 ? ' item' : ' items')"></p>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4">
            <template x-if="cart.length === 0">
                <div class="text-center py-12 text-brand-500 border border-brand-200 bg-brand-50">
                    <svg class="w-12 h-12 mx-auto mb-3 text-brand-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
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
                                    <p class="text-xs text-brand-600" x-text="formatPrice(item.price)"></p>
                                </div>
                                <button @click="removeFromCart(index)" :aria-label="'Remove ' + item.product_name" class="text-brand-400 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="decrementQuantity(index)"
                                        :aria-label="'Decrease quantity of ' + item.product_name"
                                        class="w-8 h-8 flex items-center justify-center border border-brand-300 bg-brand-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                                <span class="flex-1 text-center text-sm font-medium" x-text="item.quantity"></span>
                                <button @click="incrementQuantity(index)"
                                        :aria-label="'Increase quantity of ' + item.product_name"
                                        class="w-8 h-8 flex items-center justify-center border border-brand-300 bg-brand-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
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

        <!-- Cart Footer -->
        <div class="p-4 border-t border-brand-200 bg-brand-50">
            <p x-show="checkoutError" x-text="checkoutError" class="mb-3 text-sm text-danger" role="alert"></p>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-brand-600">Subtotal</span>
                    <span class="font-medium" x-text="formatPrice(subtotal)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-brand-600">Tax (10%)</span>
                    <span class="font-medium" x-text="formatPrice(tax)"></span>
                </div>
                <div class="flex justify-between text-lg font-semibold pt-2 border-t border-brand-200">
                    <span class="text-brand-900">Total</span>
                    <span class="text-accent-600" x-text="formatPrice(total)"></span>
                </div>
            </div>
            <button @click="processCheckout()"
                    :disabled="cart.length === 0 || processing"
                    class="w-full py-3 bg-accent-500 text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!processing">Complete Sale</span>
                <span x-show="processing" x-cloak>Processing...</span>
            </button>
        </div>
    </div>

    <!-- Checkout Success Modal -->
    <div x-show="showSuccessModal"
         x-cloak
         @keydown.escape.window="showSuccessModal = false; clearCart()"
         role="dialog"
         aria-modal="true"
         aria-label="Sale completed"
         class="fixed inset-0 bg-brand-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-brand-100 border border-brand-200 p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-success mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-brand-900 mb-2">Sale Completed</h3>
                <p class="text-sm text-brand-600 mb-4">Transaction ID: <span x-text="lastSaleId"></span></p>
                <p class="text-sm text-brand-600 mb-6">Total Amount: <span class="font-semibold" x-text="formatPrice(lastSaleTotal)"></span></p>
                <button @click="showSuccessModal = false; clearCart()"
                        class="w-full py-2 bg-brand-900 text-white font-medium">
                    New Sale
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
