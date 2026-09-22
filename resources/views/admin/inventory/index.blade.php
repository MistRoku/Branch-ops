{{-- 
    Admin Inventory Index View
    Displays stock levels across all branches with filtering
--}}

@extends('components.layouts.admin')
@section('title', 'Inventory')
@section('content')
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Inventory</h1>
                <p class="mt-1 text-sm text-gray-500">Stock levels and management</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.inventory.movements') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Movements
                </a>
                <a href="{{ route('admin.inventory.valuation') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Valuation
                </a>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isBranchManager())
                    <a href="{{ route('admin.inventory.adjust.form') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Adjust Stock
                    </a>
                @endif
            </div>
        </div>

        {{-- Filters Section --}}
        <div class="bg-white border border-gray-200 p-4">
            <form action="{{ route('admin.inventory.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Product Filter --}}
                <div>
                    <label for="product_id" class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">Product</label>
                    <select name="product_id" 
                            id="product_id" 
                            class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Branch Filter --}}
                <div>
                    <label for="branch_id" class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">Branch</label>
                    <select name="branch_id" 
                            id="branch_id" 
                            class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Low Stock Filter --}}
                <div class="flex items-end">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="low_stock_only" 
                               value="1" 
                               {{ request('low_stock_only') ? 'checked' : '' }}
                               class="w-4 h-4 border border-gray-300 focus:outline-none">
                        <span class="ml-2 text-sm text-gray-700">Low Stock Only</span>
                    </label>
                </div>

                {{-- Submit Button --}}
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- Stock Levels Table --}}
        <div class="bg-white border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Product</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">SKU</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Branch</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Location</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Reorder Level</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($stockLevels as $stock)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $stock->product->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-xs bg-gray-100 px-2 py-1">{{ $stock->product->sku }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $stock->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $stock->location ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $isLowStock = $stock->quantity <= $stock->product->reorder_level;
                                    @endphp
                                    <div class="text-sm {{ $isLowStock ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                        {{ $stock->quantity }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $stock->product->reorder_level ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($isLowStock)
                                        <x-ui.badge type="danger">Low Stock</x-ui.badge>
                                    @else
                                        <x-ui.badge type="success">In Stock</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.inventory.product-movements', $stock->product_id) }}" 
                                       class="text-gray-600 hover:text-gray-900">View History</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No stock levels found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($stockLevels->hasPages())
                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $stockLevels->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
