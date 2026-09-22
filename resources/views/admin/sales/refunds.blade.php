@extends('components.layouts.admin')
@section('title', 'Refund Register')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Refund Register</h1>
        <a href="{{ route('admin.sales.index') }}" class="text-sm text-accent-600 underline">Sales history</a>
    </div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Sale</th><th class="py-2 pr-4">Branch</th><th class="py-2 pr-4">Type</th>
            <th class="py-2 pr-4">Amount</th><th class="py-2 pr-4">Processed by</th><th class="py-2 pr-4">Reason</th><th class="py-2 pr-4">Date</th>
        </tr></thead>
        <tbody>
        @forelse($refunds as $r)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4"><a href="{{ route('admin.sales.show', $r->sale_id) }}" class="underline">{{ $r->sale?->invoice_number }}</a></td>
            <td class="py-2 pr-4">{{ $r->branch?->name }}</td>
            <td class="py-2 pr-4">{{ ucfirst($r->refund_type) }}</td>
            <td class="py-2 pr-4 font-semibold">R {{ number_format($r->refund_amount, 2) }}</td>
            <td class="py-2 pr-4">{{ $r->user?->name }}</td>
            <td class="py-2 pr-4">{{ $r->refund_reason }}</td>
            <td class="py-2 pr-4 text-brand-600">{{ $r->created_at->format('Y-m-d H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="py-8 text-center text-brand-500">No refunds recorded</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $refunds->links() }}</div>
</div>
@endsection
