@extends('components.layouts.admin')
@section('title', 'Adjust Stock')
@section('content')
<div class="max-w-xl">
    <a href="{{ route('admin.inventory.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Back to inventory</a>
    <h1 class="text-2xl font-semibold mt-2 mb-4">Adjust Stock</h1>
    @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 mb-4"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.inventory.adjust.store') }}" class="bg-white border p-6 space-y-4">@csrf
        <div><label class="block text-xs font-medium uppercase mb-1">Product *</label><select name="product_id" class="w-full border px-3 py-2 text-sm">@foreach($products as $p)<option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select></div>
        <div>
            <label class="block text-xs font-medium uppercase mb-1">Branch *</label>
            @if(auth()->user()->isSuperAdmin())
                <select name="branch_id" class="w-full border px-3 py-2 text-sm">@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>@endforeach</select>
            @else
                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}"/>
                <p class="text-sm border px-3 py-2 bg-gray-50">{{ auth()->user()->branch?->name ?? 'Your branch' }} (locked to your branch)</p>
            @endif
        </div>
        <div><label class="block text-xs font-medium uppercase mb-1">Quantity * <span class="normal-case font-normal text-gray-500">(+ in, − out)</span></label><input name="quantity" type="number" required value="{{ old('quantity') }}" placeholder="e.g. 10 or -5" class="w-full border px-3 py-2 text-sm"/></div>
        <div><label class="block text-xs font-medium uppercase mb-1">Storage location <span class="normal-case font-normal text-gray-500">(bin, shelf, warehouse zone)</span></label><input name="location" value="{{ old('location') }}" placeholder="e.g. Aisle 3, Bin B12" maxlength="100" class="w-full border px-3 py-2 text-sm"/></div>
        <div><label class="block text-xs font-medium uppercase mb-1">Reason *</label><input name="reason" required value="{{ old('reason') }}" placeholder="Damage, recount, returns..." class="w-full border px-3 py-2 text-sm"/></div>
        <div class="flex gap-2"><button class="px-4 py-2 bg-gray-900 text-white text-sm">Post adjustment</button><a href="{{ route('admin.inventory.index') }}" class="px-4 py-2 border text-sm">Cancel</a></div>
    </form>
</div>
@endsection
