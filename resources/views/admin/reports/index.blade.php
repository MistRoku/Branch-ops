<x-layouts.admin title="Reports">
<div class="bg-white border p-6"><h1 class="text-xl font-semibold">Reports</h1>
<p class="text-sm text-gray-500 mt-1">Generate and download operational reports (CSV).</p>
<div class="flex gap-2 mt-4 text-sm">
<a href="{{ route('admin.reports.export', 'sales') }}" class="px-3 py-1 border">Sales CSV</a>
<a href="{{ route('admin.reports.export', 'inventory') }}" class="px-3 py-1 border">Inventory CSV</a>
<a href="{{ route('admin.reports.export', 'movements') }}" class="px-3 py-1 border">Movements CSV</a>
</div></div>
</x-layouts.admin>
