@extends('components.layouts.admin')
@section('title', 'Edit Product')
@section('content')
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">Edit {{ $product->name }}</h1>
<form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-3">@csrf @method('PUT')
<input name="name" value="{{ old('name',$product->name) }}" required class="w-full border px-3 py-2 text-sm"/>
<input name="selling_price" type="number" step="0.01" value="{{ old('selling_price',$product->selling_price) }}" class="w-full border px-3 py-2 text-sm"/>
@if(auth()->user()->canSeeCostPrices())<input name="cost_price" type="number" step="0.01" value="{{ old('cost_price',$product->cost_price) }}" class="w-full border px-3 py-2 text-sm"/>@endif
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Save</button></form></div>
@endsection
