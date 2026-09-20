@extends('components.layouts.admin')
@section('title', 'Movements')
@section('content')
<div class="bg-white border"><h1 class="text-xl font-semibold p-6">Stock Movements</h1>
<table class="min-w-full divide-y"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs uppercase">Product</th><th class="px-6 py-3 text-left text-xs uppercase">Branch</th><th class="px-6 py-3 text-left text-xs uppercase">Change</th><th class="px-6 py-3 text-left text-xs uppercase">Date</th></tr></thead>
<tbody class="divide-y">@forelse($movements as $m)<tr><td class="px-6 py-3 text-sm">{{ $m->product?->name }}</td><td class="px-6 py-3 text-sm">{{ $m->branch?->name }}</td><td class="px-6 py-3 text-sm">{{ $m->quantity_change }}</td><td class="px-6 py-3 text-sm">{{ $m->created_at }}</td></tr>@empty<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No movements</td></tr>@endforelse</tbody></table>
@if($movements->hasPages())<div class="p-4 border-t">{{ $movements->links() }}</div>@endif</div>
@endsection
