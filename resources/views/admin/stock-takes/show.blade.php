<x-layouts.admin title="Stock Take">
<div class="bg-white border p-6 max-w-2xl"><h1 class="text-xl font-semibold">Take #{{ $take->id }} — {{ $take->status }}</h1><p class="text-sm">{{ $take->branch?->name }}</p>
<ul class="text-sm mt-4">@foreach($take->items as $it)<li>{{ $it->product?->name }}: system {{ $it->quantity_system }}, counted {{ $it->quantity_counted }}</li>@endforeach</ul>
@if($take->status !== 'completed')<form method="POST" action="{{ route('admin.stock-takes.update', $take) }}" class="mt-6">@csrf @method('PUT')<button class="px-4 py-2 bg-gray-900 text-white text-sm">Complete & post variances</button></form>@endif</div>
</x-layouts.admin>
