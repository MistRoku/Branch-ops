@extends('components.layouts.admin')
@section('title', 'New Special')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">New Special</h1>
    <form method="POST" action="{{ route('admin.specials.store') }}" class="space-y-3">@csrf
        <div><label class="block text-sm font-medium mb-1">Product</label>
        <select name="product_id" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm">
            @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) — R {{ number_format($p->selling_price, 2) }}</option>@endforeach
        </select></div>
        <div><label class="block text-sm font-medium mb-1">Branch (blank means all branches)</label>
        <select name="branch_id" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm">
            <option value="">All branches</option>
            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
        </select></div>
        <div><label class="block text-sm font-medium mb-1">Special price (R)</label>
        <input name="special_price" type="number" step="0.01" min="0" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <div class="flex gap-2">
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Starts</label><input name="starts_at" type="date" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Ends</label><input name="ends_at" type="date" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        </div>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Create special</button>
    </form>
</div>
@endsection
