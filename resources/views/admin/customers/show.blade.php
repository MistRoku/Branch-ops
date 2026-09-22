@extends('components.layouts.admin')
@section('title', 'Customer')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-2xl">
    <h1 class="text-xl font-semibold">{{ $customer->name }}</h1>
    <p class="text-sm text-brand-600">{{ $customer->phone ?? 'No phone' }} · {{ $customer->email ?? 'No email' }}</p>
    <p class="text-sm text-brand-600 mt-1">{{ $customer->sales_count }} sales · R {{ number_format($customer->sales_sum_total_amount ?? 0, 2) }} lifetime · {{ $customer->loyalty_points }} loyalty points</p>
    @if($customer->address)<p class="text-sm text-brand-600 mt-1">{{ $customer->address }}</p>@endif
    <h2 class="font-semibold mt-6 mb-2">Recent sales</h2>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200"><th class="py-2 pr-4">Invoice</th><th class="py-2 pr-4">Total</th><th class="py-2 pr-4">Date</th><th></th></tr></thead>
        <tbody>
        @forelse($customer->sales as $s)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ $s->invoice_number }}</td>
            <td class="py-2 pr-4">R {{ number_format($s->total_amount, 2) }}</td>
            <td class="py-2 pr-4">{{ $s->created_at->format('Y-m-d') }}</td>
            <td class="py-2 text-right"><a href="{{ route('admin.sales.show', $s) }}" class="underline">View</a></td>
        </tr>
        @empty
        <tr><td colspan="4" class="py-4 text-brand-500">No sales yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
