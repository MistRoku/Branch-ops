@extends('components.layouts.admin')
@section('title', 'Product Movements')
@section('content')
<div class="bg-white border p-6"><h1 class="text-xl font-semibold">{{ $product->name }} — movements</h1>
<ul class="text-sm mt-4">@forelse($movements as $m)<li>{{ $m->created_at }} — {{ $m->quantity_change }} ({{ $m->reason }})</li>@empty<li>No movements</li>@endforelse</ul>
@if($movements->hasPages())<div class="mt-4">{{ $movements->links() }}</div>@endif</div>
@endsection
