@extends('components.layouts.admin')
@section('title', 'PO')
@section('content')
<div class="bg-white border p-6 max-w-2xl"><h1 class="text-xl font-semibold">{{ $po->po_number }} — {{ $po->status }}</h1><p class="text-sm">{{ $po->supplier?->name }} · {{ $po->branch?->name }}</p>
<ul class="text-sm mt-4">@foreach($po->items as $it)<li>{{ $it->product?->name }}: ordered {{ $it->quantity_ordered }}, received {{ $it->quantity_received }}</li>@endforeach</ul>
<div class="flex gap-2 mt-6">
@if($po->status==='draft')<form method="POST" action="{{ route('admin.purchase-orders.send', $po) }}">@csrf @method('PUT')<button class="px-3 py-1 border text-sm">Send</button></form>@endif
@if(in_array($po->status,['sent','partial_received']))<a href="{{ route('admin.purchase-orders.receive', $po) }}" class="px-3 py-1 bg-gray-900 text-white text-sm">Receive</a>@endif
@if(in_array($po->status,['draft','sent']))<form method="POST" action="{{ route('admin.purchase-orders.cancel', $po) }}" onsubmit="return confirm('Cancel this purchase order?')">@csrf @method('PUT')<button class="px-3 py-1 border text-sm text-red-600">Cancel</button></form>@endif
</div></div>
@endsection
