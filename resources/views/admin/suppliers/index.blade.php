@extends('components.layouts.admin')
@section('title', 'Suppliers')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Suppliers</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $suppliers->total() }} total · manage vendor records</p>
        </div>
        <a href="{{ route('admin.suppliers.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">Add Supplier</a>
    </div>
    <div class="bg-white border border-gray-200 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Name, email or phone" class="flex-1 border border-gray-300 px-3 py-2 text-sm"/>
            <select name="is_active" class="border border-gray-300 px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option value="1" @selected(request('is_active')==='1')>Active</option>
                <option value="0" @selected(request('is_active')==='0')>Inactive</option>
            </select>
            <button class="px-4 py-2 bg-gray-900 text-white text-sm">Filter</button>
            @if(request()->hasAny(['search','is_active']))<a href="{{ route('admin.suppliers.index') }}" class="px-4 py-2 border text-sm">Clear</a>@endif
        </form>
    </div>
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="bg-white border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Supplier</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Products</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Orders</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
            @forelse($suppliers as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4"><div class="text-sm font-medium"><a href="{{ route('admin.suppliers.show', $s) }}" class="hover:underline">{{ $s->name }}</a></div><div class="text-xs text-gray-500">{{ $s->email ?? 'No email' }}</div></td>
                    <td class="px-6 py-4 text-sm">{{ $s->contact_name ?? '—' }}@if($s->phone)<div class="text-xs text-gray-500">{{ $s->phone }}</div>@endif</td>
                    <td class="px-6 py-4 text-sm">{{ $s->products_count ?? $s->products->count() }}</td>
                    <td class="px-6 py-4 text-sm">{{ $s->purchase_orders_count ?? $s->purchaseOrders->count() }}</td>
                    <td class="px-6 py-4"><span class="text-xs px-2 py-1 {{ $s->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-6 py-4 text-right text-sm space-x-2"><a href="{{ route('admin.suppliers.show', $s) }}" class="hover:underline">View</a><a href="{{ route('admin.suppliers.edit', $s) }}" class="hover:underline">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No suppliers found. <a href="{{ route('admin.suppliers.create') }}" class="underline">Add the first one</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($suppliers->hasPages())<div class="px-6 py-4 border-t">{{ $suppliers->links() }}</div>@endif
    </div>
</div>
@endsection
