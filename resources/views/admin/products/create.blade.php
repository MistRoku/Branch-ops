<x-layouts.admin title="New Product">
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">New Product</h1>
<form method="POST" action="{{ route('admin.products.store') }}" class="space-y-3">@csrf
<input name="name" required placeholder="Name" class="w-full border px-3 py-2 text-sm"/>
<input name="sku" required placeholder="SKU" class="w-full border px-3 py-2 text-sm"/>
<input name="selling_price" type="number" step="0.01" required placeholder="Selling price" class="w-full border px-3 py-2 text-sm"/>
<input name="cost_price" type="number" step="0.01" required placeholder="Cost price" class="w-full border px-3 py-2 text-sm"/>
<select name="supplier_id" class="w-full border px-3 py-2 text-sm"><option value="">No supplier</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Create</button></form></div>
</x-layouts.admin>
