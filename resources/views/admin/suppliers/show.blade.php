<x-layouts.admin title="Supplier">
<div class="bg-white border p-6 max-w-2xl">
<h1 class="text-xl font-semibold">{{ $supplier->name }}</h1>
<p class="text-sm text-gray-500">{{ $supplier->email }} · {{ $supplier->phone }}</p>
<p class="text-sm mt-2">{{ $supplier->address }}</p>
<h2 class="font-semibold mt-6 mb-2 text-sm uppercase">Products ({{ $supplier->products->count() }})</h2>
<ul class="text-sm list-disc ml-5">@foreach($supplier->products as $p)<li>{{ $p->name }} ({{ $p->sku }})</li>@endforeach</ul>
<h2 class="font-semibold mt-6 mb-2 text-sm uppercase">Purchase orders ({{ $supplier->purchaseOrders->count() }})</h2>
<ul class="text-sm list-disc ml-5">@foreach($supplier->purchaseOrders as $po)<li>#{{ $po->id }} — {{ $po->status }}</li>@endforeach</ul>
<a href="{{ route('admin.suppliers.edit', $supplier) }}" class="inline-block mt-6 text-sm underline">Edit</a>
</div>
</x-layouts.admin>
