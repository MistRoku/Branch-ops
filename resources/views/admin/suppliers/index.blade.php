<x-layouts.admin title="Suppliers">
<div class="space-y-6">
<div class="flex items-center justify-between border-b border-gray-200 pb-4">
<div><h1 class="text-2xl font-semibold text-gray-900">Suppliers</h1></div>
<a href="{{ route('admin.suppliers.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm">Add Supplier</a>
</div>
<form method="GET" class="flex gap-2"><input name="search" value="{{ request('search') }}" placeholder="Search" class="border px-3 py-2 text-sm"/><button class="px-4 py-2 bg-gray-900 text-white text-sm">Filter</button></form>
<div class="bg-white border border-gray-200"><table class="min-w-full divide-y divide-gray-200">
<thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs uppercase">Name</th><th class="px-6 py-3 text-left text-xs uppercase">Contact</th><th class="px-6 py-3 text-left text-xs uppercase">Products</th><th class="px-6 py-3 text-right text-xs uppercase">Actions</th></tr></thead>
<tbody class="divide-y divide-gray-200">@forelse($suppliers as $s)<tr><td class="px-6 py-4 text-sm font-medium">{{ $s->name }}</td><td class="px-6 py-4 text-sm">{{ $s->contact_name ?? '-' }}</td><td class="px-6 py-4 text-sm">{{ $s->products_count ?? 0 }}</td><td class="px-6 py-4 text-right text-sm"><a href="{{ route('admin.suppliers.show', $s) }}" class="hover:underline">View</a> <a href="{{ route('admin.suppliers.edit', $s) }}" class="hover:underline ml-2">Edit</a></td></tr>@empty<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No suppliers</td></tr>@endforelse</tbody>
</table>@if($suppliers->hasPages())<div class="px-6 py-4 border-t">{{ $suppliers->links() }}</div>@endif</div>
</div>
</x-layouts.admin>
