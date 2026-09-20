<x-layouts.admin title="Valuation">
<div class="bg-white border p-6"><h1 class="text-xl font-semibold">Inventory Valuation</h1>
<p class="text-2xl font-bold mt-2">R{{ number_format($valuation['total_value'] ?? 0, 2) }}</p>
<h2 class="text-sm uppercase font-semibold mt-6">By branch</h2>
<ul class="text-sm">@foreach($valuation['by_branch'] ?? [] as $b)<li>{{ $b['branch_name'] }}: R{{ number_format($b['total_value'],2) }} ({{ $b['item_count'] }} lines)</li>@endforeach</ul>
</div>
</x-layouts.admin>
