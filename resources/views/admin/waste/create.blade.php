@extends('components.layouts.admin')
@section('title', 'Log Waste')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">Log Waste</h1>
    <form method="POST" action="{{ route('admin.waste.store') }}" class="space-y-3">@csrf
        <div><label class="block text-sm font-medium mb-1">Product</label>
        <select name="product_id" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm">
            @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) — per {{ $p->unit_of_measure ?? 'piece' }}</option>@endforeach
        </select></div>
        <div class="flex gap-2">
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Quantity</label><input name="quantity" type="number" min="1" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Unit of measure</label><input name="uom" maxlength="50" placeholder="piece" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        </div>
        <div><label class="block text-sm font-medium mb-1">Reason (expired, damaged, spoiled)</label><input name="reason" required maxlength="500" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Log waste</button>
    </form>
</div>
@endsection
