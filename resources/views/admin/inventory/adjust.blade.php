<x-layouts.admin title="Adjust Stock">
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">Adjust Stock</h1>
<form method="POST" action="{{ route('admin.inventory.adjust.store') }}" class="space-y-3">@csrf
<select name="product_id" class="w-full border px-3 py-2 text-sm">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
<select name="branch_id" class="w-full border px-3 py-2 text-sm">@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
<input name="quantity" type="number" required placeholder="+/- qty" class="w-full border px-3 py-2 text-sm"/>
<input name="reason" required placeholder="Reason" class="w-full border px-3 py-2 text-sm"/>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Adjust</button></form></div>
</x-layouts.admin>
