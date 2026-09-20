@extends('components.layouts.admin')
@section('title', $supplier->name)
@section('content')
<div class="space-y-6 max-w-3xl">
    <a href="{{ route('admin.suppliers.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Back to suppliers</a>
    <div class="bg-white border p-6">
        <div class="flex items-start justify-between">
            <div><h1 class="text-2xl font-semibold">{{ $supplier->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $supplier->contact_name ?? 'No contact' }} · {{ $supplier->email ?? 'No email' }} · {{ $supplier->phone ?? 'No phone' }}</p></div>
            <span class="text-xs px-2 py-1 {{ $supplier->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        @if($supplier->address)<p class="text-sm mt-3">{{ $supplier->address }}</p>@endif
        @if($supplier->tax_id)<p class="text-xs text-gray-500 mt-1">Tax ID: {{ $supplier->tax_id }}</p>@endif
        @if($supplier->notes)<p class="text-sm mt-3 border-t pt-3">{{ $supplier->notes }}</p>@endif
        <div class="flex gap-2 mt-4">
            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="px-4 py-2 border text-sm">Edit</a>
            @if(auth()->user()->isSuperAdmin() && ($supplier->products->count() + $supplier->purchaseOrders->count()) === 0)
            <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Deactivate this supplier?')">@csrf @method('DELETE')<button class="px-4 py-2 border border-red-300 text-red-600 text-sm">Deactivate</button></form>
            @endif
        </div>
    </div>
    <div class="bg-white border">
        <h2 class="font-semibold text-sm uppercase px-6 py-3 border-b">Products ({{ $supplier->products->count() }})</h2>
        <ul class="divide-y">@forelse($supplier->products as $p)<li class="px-6 py-3 text-sm flex justify-between"><span>{{ $p->name }} <span class="text-gray-500">({{ $p->sku }})</span></span><a href="{{ route('admin.products.show', $p) }}" class="underline">View</a></li>@empty<li class="px-6 py-4 text-sm text-gray-500">No products linked.</li>@endforelse</ul>
    </div>
    <div class="bg-white border">
        <h2 class="font-semibold text-sm uppercase px-6 py-3 border-b">Purchase orders ({{ $supplier->purchaseOrders->count() }})</h2>
        <ul class="divide-y">@forelse($supplier->purchaseOrders as $po)<li class="px-6 py-3 text-sm flex justify-between"><span>{{ $po->po_number }} — {{ $po->status }}</span><a href="{{ route('admin.purchase-orders.show', $po) }}" class="underline">View</a></li>@empty<li class="px-6 py-4 text-sm text-gray-500">No purchase orders.</li>@endforelse</ul>
    </div>
</div>
@endsection
