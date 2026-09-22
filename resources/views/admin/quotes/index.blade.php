@extends('components.layouts.admin')
@section('title', 'Quotations')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <h1 class="text-xl font-semibold mb-1">Quotations</h1>
    <p class="text-sm text-brand-600 mb-4">Quotes saved at the till. Print a proforma invoice for the customer, then convert to a sale when they accept.</p>
    <form method="GET" class="flex gap-2 mb-4 text-sm">
        <select name="status" class="border border-brand-300 bg-brand-50 px-2 py-1">
            <option value="">All statuses</option>
            @foreach(['draft','sent','accepted','expired','converted'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="px-3 py-1 bg-brand-900 text-white">Filter</button>
    </form>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Number</th><th class="py-2 pr-4">Customer</th><th class="py-2 pr-4">Total</th><th class="py-2 pr-4">Valid until</th><th class="py-2 pr-4">Status</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($quotes as $q)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4 font-medium">{{ $q->quote_number }}</td>
            <td class="py-2 pr-4">{{ $q->customer?->name ?? 'Walk-in' }}</td>
            <td class="py-2 pr-4 font-semibold">R {{ number_format($q->total_amount, 2) }}</td>
            <td class="py-2 pr-4">{{ $q->valid_until?->format('Y-m-d') ?? '—' }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs text-white {{ $q->status === 'converted' ? 'bg-success' : ($q->status === 'expired' ? 'bg-danger' : 'bg-info') }}">{{ ucfirst($q->status) }}</span></td>
            <td class="py-2 text-right whitespace-nowrap"><a href="{{ route('admin.quotes.show', $q) }}" class="underline">View</a><a href="{{ route('admin.quotes.proforma', $q) }}" class="underline ml-2">Proforma</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="py-8 text-center text-brand-500">No quotations yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $quotes->links() }}</div>
</div>
@endsection
