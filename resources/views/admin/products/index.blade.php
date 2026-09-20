{{-- 
    Admin Products Index View
    Displays a list of all products with filtering and search capabilities
--}}

<x-layouts.admin title="Products">
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Products</h1>
                <p class="mt-1 text-sm text-gray-500">Manage your product catalog</p>
            </div>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.products.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Product
                </a>
            @endif
        </div>

        {{-- Filters Section --}}
        <div class="bg-white border border-gray-200 p-4">
            <form action="{{ route('admin.products.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search Input --}}
                <div>
                    <label for="search" class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">Search</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           value="{{ request('search') }}" 
                           placeholder="Name, SKU, or barcode"
                           class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                </div>

                {{-- Supplier Filter --}}
                <div>
                    <label for="supplier_id" class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">Supplier</label>
                    <select name="supplier_id" 
                            id="supplier_id" 
                            class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Active Status Filter --}}
                <div>
                    <label for="is_active" class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">Status</label>
                    <select name="is_active" 
                            id="is_active" 
                            class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
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

        {{-- Products Table --}}
        <div class="bg-white border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Product</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">SKU</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Stock</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($products as $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                    @if($product->description)
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($product->description, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-xs bg-gray-100 px-2 py-1">{{ $product->sku }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $product->supplier?->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">${{ number_format($product->selling_price, 2) }}</div>
                                    @if(auth()->user()->canSeeCostPrices())
                                        <div class="text-xs text-gray-500">Cost: ${{ number_format($product->cost_price, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $totalStock = $product->stockLevels->sum('quantity');
                                        $isLowStock = $totalStock <= $product->reorder_level;
                                    @endphp
                                    <div class="text-sm {{ $isLowStock ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                        {{ $totalStock }}
                                    </div>
                                    @if($isLowStock)
                                        <div class="text-xs text-red-600">Low Stock</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge :type="$product->is_active ? 'success' : 'secondary'">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('admin.products.show', $product) }}" 
                                           class="text-gray-600 hover:text-gray-900">View</a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->canSeeCostPrices())
                                            <a href="{{ route('admin.products.edit', $product) }}" 
                                               class="text-gray-600 hover:text-gray-900">Edit</a>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin())
                                            <form action="{{ route('admin.products.destroy', $product) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to deactivate this product?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Deactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No products found</p>
                                    @if(auth()->user()->isSuperAdmin())
                                        <a href="{{ route('admin.products.create') }}" class="mt-2 inline-block text-sm font-medium text-gray-900 hover:underline">Add your first product</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
