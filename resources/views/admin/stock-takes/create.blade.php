<x-layouts.admin title="New Stock Take">
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">New Stock Take</h1>
<form method="POST" action="{{ route('admin.stock-takes.store') }}" class="space-y-3">@csrf
<select name="branch_id" class="w-full border px-3 py-2 text-sm">@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
<textarea name="notes" placeholder="Notes" class="w-full border px-3 py-2 text-sm"></textarea>
<div class="space-y-2">@for($i=0;$i<5;$i++)<div class="flex gap-2"><select name="items[{{ $i }}][product_id]" class="flex-1 border px-2 py-1 text-sm"><option value="">— product —</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select><input name="items[{{ $i }}][quantity_counted]" type="number" min="0" placeholder="Counted" class="w-24 border px-2 py-1 text-sm"/></div>@endfor</div>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Open</button></form></div>
</x-layouts.admin>
