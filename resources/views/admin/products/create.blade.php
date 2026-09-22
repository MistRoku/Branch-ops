@extends('components.layouts.admin')
@section('title', 'New Product')
@section('content')
<div class="max-w-xl bg-brand-100 border border-brand-200 p-6"><h1 class="text-xl font-semibold mb-4">New Product</h1>
<form method="POST" action="{{ route('admin.products.store') }}" class="space-y-3">@csrf
<label class="block text-sm font-medium">Name<input name="name" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">SKU<input name="sku" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">Barcode<input name="barcode" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<label class="block text-sm font-medium">Description<input name="description" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Selling price (R)<input name="selling_price" type="number" step="0.01" min="0" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">Cost price (R)<input name="cost_price" type="number" step="0.01" min="0" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Tax rate %<input name="tax_rate" type="number" step="0.01" min="0" max="100" value="15" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">Unit of measure<input name="unit_of_measure" placeholder="piece" maxlength="50" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">Reorder level<input name="reorder_level" type="number" min="0" value="10" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<label class="block text-sm font-medium">Supplier<select name="supplier_id" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option value="">No supplier</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></label>
<h2 class="font-semibold pt-2">Opening stock per branch</h2>
@foreach($branches as $b)
<div class="flex gap-2 items-center text-sm">
<span class="flex-1">{{ $b->name }}</span>
<input type="hidden" name="initial_stock[{{ $loop->index }}][branch_id]" value="{{ $b->id }}"/>
<input name="initial_stock[{{ $loop->index }}][quantity]" type="number" min="0" value="0" class="w-28 border border-brand-300 bg-brand-50 px-3 py-2 text-sm"/>
</div>
@endforeach
<button class="px-4 py-2 bg-brand-900 text-white text-sm">Create product with stock</button></form></div>
@endsection
