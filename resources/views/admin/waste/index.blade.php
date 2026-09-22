@extends('components.layouts.admin')
@section('title', 'Waste Log')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Waste Log</h1>
        <a href="{{ route('admin.waste.create') }}" class="px-4 py-2 bg-brand-900 text-white text-sm">Log waste</a>
    </div>
    <p class="text-sm text-brand-600 mb-4">Damaged, expired or spoiled stock written off. Logging waste reduces branch stock and keeps an audit trail with reason and quantity in the product unit of measure.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Product</th><th class="py-2 pr-4">Branch</th><th class="py-2 pr-4">Quantity</th>
            <th class="py-2 pr-4">Reason</th><th class="py-2 pr-4">Logged by</th><th class="py-2 pr-4">Date</th>
        </tr></thead>
        <tbody>
        @forelse($logs as $w)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ $w->product?->name }} <span class="text-brand-500 text-xs">{{ $w->product?->sku }}</span></td>
            <td class="py-2 pr-4">{{ $w->branch?->name }}</td>
            <td class="py-2 pr-4 font-semibold">{{ $w->quantity }} {{ $w->uom }}</td>
            <td class="py-2 pr-4">{{ $w->reason }}</td>
            <td class="py-2 pr-4">{{ $w->user?->name }}</td>
            <td class="py-2 pr-4 text-brand-600">{{ $w->logged_at->format('Y-m-d H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="py-8 text-center text-brand-500">No waste logged</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
