@extends('components.layouts.admin')
@section('title', 'Quotation ' . $quote->quote_number)
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-2xl">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold">{{ $quote->quote_number }}</h1>
            <p class="text-sm text-brand-600">{{ $quote->customer?->name ?? 'Walk-in customer' }} · Valid until {{ $quote->valid_until?->format('Y-m-d') ?? '—' }} · By {{ $quote->user?->name }}</p>
        </div>
        <span class="px-2 py-1 text-xs text-white {{ $quote->status === 'converted' ? 'bg-success' : 'bg-info' }}">{{ ucfirst($quote->status) }}</span>
    </div>
    <table class="w-full text-sm mb-4">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200"><th class="py-2 pr-4">Product</th><th class="py-2 pr-4">Qty</th><th class="py-2 pr-4">Price</th><th class="py-2 text-right">Line total</th></tr></thead>
        <tbody>
        @foreach($quote->items as $it)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ $it->product?->name }}</td><td class="py-2 pr-4">{{ $it->quantity }}</td>
            <td class="py-2 pr-4">R {{ number_format($it->unit_price, 2) }}</td><td class="py-2 text-right">R {{ number_format($it->total, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="text-sm space-y-1 max-w-xs ml-auto">
        <div class="flex justify-between"><span class="text-brand-600">Subtotal</span><span>R {{ number_format($quote->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-brand-600">Discount</span><span>R {{ number_format($quote->discount_amount, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-brand-600">Tax</span><span>R {{ number_format($quote->tax_amount, 2) }}</span></div>
        <div class="flex justify-between font-semibold text-base pt-1 border-t border-brand-200"><span>Total</span><span>R {{ number_format($quote->total_amount, 2) }}</span></div>
    </div>
    @if($quote->notes)<p class="text-sm text-brand-600 mt-4">Notes: {{ $quote->notes }}</p>@endif
    <div class="flex gap-2 mt-6">
        <a href="{{ route('admin.quotes.proforma', $quote) }}" class="px-4 py-2 bg-brand-900 text-white text-sm">Print proforma</a>
        <a href="{{ route('admin.quotes.index') }}" class="px-4 py-2 border border-brand-300 text-sm">Back</a>
    </div>
</div>
@endsection
