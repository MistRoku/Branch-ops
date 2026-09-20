@extends('components.layouts.pos')

@section('title', 'POS Terminal - BranchOps')

@section('content')
<div class="h-full flex" x-data="posTerminal({ branchId: {{ auth()->user()->branch_id ?? 'null' }} })">
    <!-- Left Panel - Product Grid -->
    <div class="flex-1 overflow-hidden flex flex-col">
        <!-- Search -->
        <div class="p-4 border-b border-gray-200 bg-white">
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <label for="pos-search" class="sr-only">Search products</label>
                    <input id="pos-search"
                           type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="filterProducts()"
                           placeholder="Search products by name, SKU or barcode..."
                           autocomplete="off"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 focus:border-blue-600 focus:ring-0 text-sm">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            <p x-show="loadError" x-text="loadError" class="mt-2 text-sm text-red-600" role="alert"></p>
        </div>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <template x-if="loading">
                    <template x-for="i in 8" :key="i">
                        <div class="bg-white border border-gray-200 p-4 animate-pulse" aria-hidden="true">
                            <div class="h-24 bg-gray-200 mb-3"></div>
                            <div class="h-4 bg-gray-200 w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 w-1/2"></div>
                        </div>
                    </template>
                </template>
                <template x-if="!loading && filteredProducts.length === 0">
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <p x-text="products.length === 0 ? 'No products available' : 'No products match your search'"></p>
                    </div>
                </template>
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="addToCart(product)"
                            :disabled="product.stock <= 0"
                            class="bg-white border border-gray-200 p-4 text-left hover:border-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <div class="h-24 bg-gray-100 mb-3 flex items-center justify-center">
                            <span class="text-3xl text-gray-400" aria-hidden="true">📦</span>
                        </div>
                        <h3 class="font-medium text-slate-900 text-sm mb-1" x-text="product.name"></h3>
                        <p class="text-xs text-gray-500 mb-2" x-text="product.sku"></p>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-blue-600" x-text="formatPrice(product.unitPrice)"></span>
                            <span class="text-xs px-2 py-1"
                                  :class="product.stock > 10 ? 'bg-green-100 text-green-800' : (product.stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')"
                                  x-text="product.stock > 0 ? product.stock + ' left' : 'Out of stock'"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Right Panel - Cart -->
    <div class="w-96 bg-white border-l border-gray-200 flex flex-col">
        <!-- Cart Header -->
        <div class="p-4 border-b border-gray-200">
            <h2 class="font-semibold text-slate-900">Current Sale</h2>
            <p class="text-xs text-gray-500 mt-1" x-text="cartCount + (cartCount === 1 ? ' item' : ' items')"></p>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4">
            <template x-if="cart.length === 0">
                <div class="text-center py-12 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm">Cart is empty</p>
                    <p class="text-xs mt-1">Click products to add them</p>
                </div>
            </template>
            <template x-if="cart.length > 0">
                <div class="space-y-3">
                    <template x-for="(item, index) in cart" :key="item.product_id">
                        <div class="border border-gray-200 p-3">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm text-slate-900" x-text="item.product_name"></h4>
                                    <p class="text-xs text-gray-500" x-text="formatPrice(item.price)"></p>
                                </div>
                                <button @click="removeFromCart(index)" :aria-label="'Remove ' + item.product_name" class="text-gray-400 hover:text-red-600 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="decrementQuantity(index)"
                                        :aria-label="'Decrease quantity of ' + item.product_name"
                                        class="w-8 h-8 flex items-center justify-center border border-gray-300 hover:bg-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <span class="flex-1 text-center text-sm font-medium" x-text="item.quantity"></span>
                                <button @click="incrementQuantity(index)"
                                        :aria-label="'Increase quantity of ' + item.product_name"
                                        class="w-8 h-8 flex items-center justify-center border border-gray-300 hover:bg-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-200 flex justify-between items-center">
                                <span class="text-xs text-gray-500">Subtotal</span>
                                <span class="font-semibold text-sm" x-text="formatPrice(item.price * item.quantity)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Cart Footer -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <p x-show="checkoutError" x-text="checkoutError" class="mb-3 text-sm text-red-600" role="alert"></p>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium" x-text="formatPrice(subtotal)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Tax (10%)</span>
                    <span class="font-medium" x-text="formatPrice(tax)"></span>
                </div>
                <div class="flex justify-between text-lg font-semibold pt-2 border-t border-gray-200">
                    <span class="text-slate-900">Total</span>
                    <span class="text-blue-600" x-text="formatPrice(total)"></span>
                </div>
            </div>
            <button @click="processCheckout()"
                    :disabled="cart.length === 0 || processing"
                    class="w-full py-3 bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                <span x-show="!processing">Complete Sale</span>
                <span x-show="processing">Processing...</span>
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
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     style="display: none;">
    <div class="bg-white p-6 max-w-md w-full">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Sale Completed!</h3>
            <p class="text-sm text-gray-600 mb-4">Transaction ID: <span x-text="lastSaleId"></span></p>
            <p class="text-sm text-gray-600 mb-6">Total Amount: <span class="font-semibold" x-text="formatPrice(lastSaleTotal)"></span></p>
            <button @click="showSuccessModal = false; clearCart()"
                    class="w-full py-2 bg-slate-900 text-white font-medium hover:bg-slate-800">
                New Sale
            </button>
        </div>
    </div>
</div>
</div>
@endsection
