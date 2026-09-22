@extends('components.layouts.admin')
@section('title', 'Sale ' . $sale->invoice_number)
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-3xl">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold">{{ $sale->invoice_number }}</h1>
            <p class="text-sm text-brand-600">{{ $sale->branch?->name }} · {{ $sale->created_at->format('Y-m-d H:i') }} · Cashier: {{ $sale->user?->name }}</p>
        </div>
        <span class="px-2 py-1 text-xs text-white {{ $sale->status === 'completed' ? 'bg-success' : ($sale->status === 'void' ? 'bg-danger' : 'bg-warning') }}">{{ ucfirst($sale->status) }}</span>
    </div>
    <table class="w-full text-sm mb-4">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Product</th><th class="py-2 pr-4">UOM</th><th class="py-2 pr-4">Qty</th><th class="py-2 pr-4">Price</th><th class="py-2 text-right">Line total</th>
        </tr></thead>
        <tbody>
        @foreach($sale->items as $it)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ $it->product?->name }} <span class="text-brand-500 text-xs">{{ $it->product?->sku }}</span></td>
            <td class="py-2 pr-4">{{ $it->product?->unit_of_measure ?? 'piece' }}</td>
            <td class="py-2 pr-4">{{ $it->quantity }}</td>
            <td class="py-2 pr-4">R {{ number_format($it->unit_price, 2) }}</td>
            <td class="py-2 text-right">R {{ number_format($it->total, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="text-sm space-y-1 max-w-xs ml-auto">
        <div class="flex justify-between"><span class="text-brand-600">Subtotal</span><span>R {{ number_format($sale->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-brand-600">Discount @if($sale->coupon_code) ({{ $sale->coupon_code }}) @endif</span><span>R {{ number_format($sale->discount_amount, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-brand-600">Tax</span><span>R {{ number_format($sale->tax_amount, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-brand-600">Tip</span><span>R {{ number_format($sale->tip_amount, 2) }}</span></div>
        <div class="flex justify-between font-semibold text-base pt-1 border-t border-brand-200"><span>Total</span><span>R {{ number_format($sale->total_amount, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-brand-600">Paid ({{ ucfirst($sale->payment_method) }})</span><span>@if($sale->tendered_amount !== null) R {{ number_format($sale->tendered_amount, 2) }} @else — @endif</span></div>
        @if($sale->change_amount > 0)<div class="flex justify-between"><span class="text-brand-600">Change</span><span>R {{ number_format($sale->change_amount, 2) }}</span></div>@endif
        @if($sale->payment_reference)<div class="flex justify-between"><span class="text-brand-600">Reference</span><span>{{ $sale->payment_reference }}</span></div>@endif
    </div>
    @if($sale->notes)<p class="text-sm text-brand-600 mt-4">Notes: {{ $sale->notes }}</p>@endif
    @if($sale->status === 'void')
    <div class="mt-4 p-3 border border-danger text-danger text-sm">Voided by {{ $sale->voidedBy?->name }} on {{ $sale->voided_at?->format('Y-m-d H:i') }}. Reason: {{ $sale->void_reason }}</div>
    @endif
    @if($sale->refunds->count())
    <h2 class="font-semibold mt-6 mb-2">Refunds</h2>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200"><th class="py-2 pr-4">Type</th><th class="py-2 pr-4">Amount</th><th class="py-2 pr-4">By</th><th class="py-2 pr-4">Reason</th><th class="py-2 pr-4">Date</th></tr></thead>
        <tbody>@foreach($sale->refunds as $r)<tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ ucfirst($r->refund_type) }}</td><td class="py-2 pr-4">R {{ number_format($r->refund_amount, 2) }}</td>
            <td class="py-2 pr-4">{{ $r->user?->name }}</td><td class="py-2 pr-4">{{ $r->refund_reason }}</td><td class="py-2 pr-4">{{ $r->created_at->format('Y-m-d H:i') }}</td>
        </tr>@endforeach</tbody>
    </table>
    @endif
    <div class="flex gap-2 mt-6">
        <a href="{{ route('admin.sales.receipt', $sale) }}" class="px-4 py-2 bg-brand-900 text-white text-sm">Print receipt</a>
        <a href="{{ route('admin.sales.index') }}" class="px-4 py-2 border border-brand-300 text-sm">Back to history</a>
    </div>
</div>
@endsection
