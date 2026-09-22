@extends('components.layouts.admin')
@section('title', 'Sales History')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Sales History</h1>
        <a href="{{ route('admin.sales.refunds') }}" class="text-sm text-accent-600 underline">Refund register</a>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 mb-4 text-sm">
        <input name="search" value="{{ request('search') }}" placeholder="Invoice number" class="border border-brand-300 bg-brand-50 px-2 py-1" />
        @if($branches->count())
        <select name="branch_id" class="border border-brand-300 bg-brand-50 px-2 py-1">
            <option value="">All branches</option>
            @foreach($branches as $b)<option value="{{ $b->id }}" @selected((string)$branchId === (string)$b->id)>{{ $b->name }}</option>@endforeach
        </select>
        @endif
        <select name="status" class="border border-brand-300 bg-brand-50 px-2 py-1">
            <option value="">All statuses</option>
            @foreach(['completed','refunded','void'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <select name="payment_method" class="border border-brand-300 bg-brand-50 px-2 py-1">
            <option value="">All payments</option>
            @foreach(['cash','card','mobile','credit'] as $p)<option value="{{ $p }}" @selected(request('payment_method') === $p)>{{ ucfirst($p) }}</option>@endforeach
        </select>
        <input name="from" type="date" value="{{ request('from') }}" class="border border-brand-300 bg-brand-50 px-2 py-1" />
        <input name="to" type="date" value="{{ request('to') }}" class="border border-brand-300 bg-brand-50 px-2 py-1" />
        <button class="px-3 py-1 bg-brand-900 text-white">Filter</button>
        <a href="{{ route('admin.sales.index') }}" class="px-3 py-1 border border-brand-300">Clear</a>
    </form>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Invoice</th><th class="py-2 pr-4">Branch</th><th class="py-2 pr-4">Cashier</th>
            <th class="py-2 pr-4">Payment</th><th class="py-2 pr-4">Total</th><th class="py-2 pr-4">Status</th><th class="py-2 pr-4">Date</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($sales as $s)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4 font-medium">{{ $s->invoice_number }}</td>
            <td class="py-2 pr-4">{{ $s->branch?->name }}</td>
            <td class="py-2 pr-4">{{ $s->user?->name }}</td>
            <td class="py-2 pr-4">{{ ucfirst($s->payment_method) }}</td>
            <td class="py-2 pr-4 font-semibold">R {{ number_format($s->total_amount, 2) }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs text-white {{ $s->status === 'completed' ? 'bg-success' : ($s->status === 'void' ? 'bg-danger' : 'bg-warning') }}">{{ ucfirst($s->status) }}</span></td>
            <td class="py-2 pr-4 text-brand-600">{{ $s->created_at->format('Y-m-d H:i') }}</td>
            <td class="py-2 text-right whitespace-nowrap">
                <a href="{{ route('admin.sales.show', $s) }}" class="underline">View</a>
                <a href="{{ route('admin.sales.receipt', $s) }}" class="underline ml-2">Receipt</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="py-8 text-center text-brand-500">No sales found</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="mt-4">{{ $sales->links() }}</div>
</div>
@endsection
