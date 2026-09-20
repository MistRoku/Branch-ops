@extends('components.layouts.admin')
@section('title', 'Product')
@section('content')
<div class="bg-white border p-6 max-w-2xl"><h1 class="text-xl font-semibold">{{ $product->name }}</h1><p class="text-sm text-gray-500">{{ $product->sku }} · {{ $product->supplier?->name }}</p>
<p class="text-sm mt-2">Sell: R{{ number_format($product->selling_price,2) }} @if(auth()->user()->canSeeCostPrices()) · Cost: R{{ number_format($product->cost_price,2) }}@endif</p>
<h2 class="text-sm uppercase font-semibold mt-4">Stock by branch</h2><ul class="text-sm">@foreach($product->stockLevels as $sl)<li>{{ $sl->branch?->name }}: {{ $sl->quantity }}</li>@endforeach</ul>
<a href="{{ route('admin.products.edit', $product) }}" class="text-sm underline mt-4 inline-block">Edit</a></div>
@endsection
