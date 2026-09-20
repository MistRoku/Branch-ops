@extends('components.layouts.admin')
@section('title', 'Transfer')
@section('content')
<div class="bg-white border p-6 max-w-2xl"><h1 class="text-xl font-semibold">{{ $transfer->transfer_number }} — {{ $transfer->status }}</h1>
<p class="text-sm">{{ $transfer->fromBranch?->name }} → {{ $transfer->toBranch?->name }}</p>
<ul class="text-sm mt-4">@foreach($transfer->items as $it)<li>{{ $it->product?->name }}: req {{ $it->quantity_requested }}, recv {{ $it->quantity_received }}</li>@endforeach</ul>
<div class="flex gap-2 mt-6">
@if($transfer->canBeApproved())<form method="POST" action="{{ route('admin.transfers.approve', $transfer) }}">@csrf @method('PUT')<button class="px-3 py-1 border text-sm">Approve</button></form>@endif
@if($transfer->canBeReceived())<form method="POST" action="{{ route('admin.transfers.receive', $transfer) }}">@csrf @method('PUT')<button class="px-3 py-1 bg-gray-900 text-white text-sm">Receive</button></form>@endif
@if($transfer->canBeRejected())<form method="POST" action="{{ route('admin.transfers.reject', $transfer) }}" onsubmit="return confirm('Reject this transfer?')">@csrf @method('PUT')<input name="rejection_reason" placeholder="Reason (optional)" class="border px-2 py-1 text-sm mr-2"/><button class="px-3 py-1 border text-sm text-red-600">Reject</button></form>@endif
</div></div>
@endsection
