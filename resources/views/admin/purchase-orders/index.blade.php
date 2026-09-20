@extends('components.layouts.admin')
@section('title', 'Purchase Orders')
@section('content')
<div class="space-y-4"><div class="flex justify-between border-b pb-4"><h1 class="text-2xl font-semibold">Purchase Orders</h1><a href="{{ route('admin.purchase-orders.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm">New PO</a></div>
<div class="bg-white border"><table class="min-w-full divide-y"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs uppercase">Number</th><th class="px-6 py-3 text-left text-xs uppercase">Supplier</th><th class="px-6 py-3 text-left text-xs uppercase">Status</th><th class="px-6 py-3 text-right text-xs uppercase">Actions</th></tr></thead>
<tbody class="divide-y">@forelse($orders as $o)<tr><td class="px-6 py-3 text-sm">{{ $o->po_number }}</td><td class="px-6 py-3 text-sm">{{ $o->supplier?->name }}</td><td class="px-6 py-3 text-sm">{{ $o->status }}</td><td class="px-6 py-3 text-right text-sm"><a href="{{ route('admin.purchase-orders.show', $o) }}" class="underline">View</a></td></tr>@empty<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No orders</td></tr>@endforelse</tbody></table></div></div>
@endsection
