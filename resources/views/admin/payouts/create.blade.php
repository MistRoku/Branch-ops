@extends('components.layouts.admin')
@section('title', 'Log Payout')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">Log Payout</h1>
    <form method="POST" action="{{ route('admin.payouts.store') }}" class="space-y-3">@csrf
        <div><label class="block text-sm font-medium mb-1">Branch</label>
        <select name="branch_id" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm">
            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
        </select></div>
        <div><label class="block text-sm font-medium mb-1">Payee</label><input name="payee" required maxlength="255" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <div><label class="block text-sm font-medium mb-1">Amount (R)</label><input name="amount" type="number" step="0.01" min="0.01" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <div><label class="block text-sm font-medium mb-1">Reason</label><input name="reason" required maxlength="500" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Submit for approval</button>
    </form>
</div>
@endsection
