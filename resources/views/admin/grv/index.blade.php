@extends('components.layouts.admin')
@section('title', 'GRV Register')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <h1 class="text-xl font-semibold mb-1">Goods Received Vouchers</h1>
    <p class="text-sm text-brand-600 mb-4">Every receipt of supplier stock creates a GRV record: who received it, where, what quantities in which unit of measure, and the delivery note. Print the voucher to file with the supplier invoice.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">GRV</th><th class="py-2 pr-4">PO</th><th class="py-2 pr-4">Branch</th>
            <th class="py-2 pr-4">Received by</th><th class="py-2 pr-4">Items</th><th class="py-2 pr-4">Date</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($grvs as $g)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4 font-medium">{{ $g->grv_number }}</td>
            <td class="py-2 pr-4">{{ $g->purchaseOrder?->po_number }} <span class="text-brand-500 text-xs">{{ $g->purchaseOrder?->supplier?->name }}</span></td>
            <td class="py-2 pr-4">{{ $g->branch?->name }}</td>
            <td class="py-2 pr-4">{{ $g->receiver?->name }}</td>
            <td class="py-2 pr-4">@foreach($g->items as $it)<span class="block text-xs">{{ $it['product_name'] ?? '' }}: {{ $it['quantity'] ?? '' }} {{ $it['uom'] ?? '' }}</span>@endforeach</td>
            <td class="py-2 pr-4 text-brand-600">{{ $g->received_at->format('Y-m-d H:i') }}</td>
            <td class="py-2 text-right"><a href="{{ route('admin.purchase-orders.grv', $g->purchase_order_id) }}" class="underline">Print</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="py-8 text-center text-brand-500">No goods received yet. Receive a purchase order to issue the first GRV.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $grvs->links() }}</div>
</div>
@endsection
