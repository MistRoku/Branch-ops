<x-layouts.admin title="Receive PO">
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">Receive {{ $po->po_number }}</h1>
<form method="POST" action="{{ route('admin.purchase-orders.receive.store', $po) }}" class="space-y-2">@csrf
@foreach($po->items as $i)<div class="flex gap-2 items-center text-sm"><span class="flex-1">{{ $i->product?->name }} ({{ $i->quantity_received }}/{{ $i->quantity_ordered }})</span><input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $i->product_id }}"/><input name="items[{{ $loop->index }}][quantity]" type="number" min="0" max="{{ $i->quantity_ordered - $i->quantity_received }}" value="0" class="w-24 border px-2 py-1"/></div>@endforeach
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Post receipt</button></form></div>
</x-layouts.admin>
