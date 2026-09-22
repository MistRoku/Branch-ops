@extends('components.layouts.admin')
@section('title', 'New Transfer')
@section('content')
<div class="max-w-2xl">
    <a href="{{ route('admin.transfers.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Back to transfers</a>
    <h1 class="text-2xl font-semibold mt-2 mb-4">New Transfer</h1>
    @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 mb-4"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.transfers.store') }}" class="bg-white border p-6 space-y-4" x-data="{ rows: 1, from: '{{ old('from_branch_id', $branches->first()?->id) }}' }">@csrf
        <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium uppercase mb-1">From *</label><select name="from_branch_id" x-model="from" class="w-full border px-3 py-2 text-sm">@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('from_branch_id')==$b->id)>{{ $b->name }}</option>@endforeach</select></div>
            <div><label class="block text-xs font-medium uppercase mb-1">To *</label><select name="to_branch_id" class="w-full border px-3 py-2 text-sm">@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('to_branch_id')==$b->id)>{{ $b->name }}</option>@endforeach</select></div>
        </div>
        <div class="border border-brand-200 bg-brand-50 p-3 text-sm" x-data="{ stock: @json($stockMap) }">
            <p class="font-medium mb-2">Stock on hand at source (updates with From branch)</p>
            <div class="max-h-40 overflow-y-auto">
            <table class="w-full text-xs">
                <thead><tr class="text-left uppercase text-brand-600"><th class="py-1 pr-2">Product</th><th class="py-1 pr-2">On hand</th><th class="py-1">Location</th></tr></thead>
                <tbody>
                @foreach($products as $p)
                <tr x-show="stock[{{ $p->id }}] && stock[{{ $p->id }}][from]">
                    <td class="py-1 pr-2">{{ $p->name }} ({{ $p->sku }})</td>
                    <td class="py-1 pr-2 font-semibold" x-text="stock[{{ $p->id }}][from].qty"></td>
                    <td class="py-1" x-text="stock[{{ $p->id }}][from].location ?? '—'"></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium uppercase mb-1">Items (leave unused rows empty)</label>
            <div class="space-y-2">
                @for($i=0;$i<5;$i++)
                <div class="flex gap-2" x-show="rows > {{ $i }}" x-cloak>
                    <select name="items[{{ $i }}][product_id]" class="flex-1 border px-2 py-1 text-sm"><option value="">— product —</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select>
                    <input name="items[{{ $i }}][quantity]" type="number" min="1" placeholder="Qty" class="w-24 border px-2 py-1 text-sm"/>
                </div>
                @endfor
            </div>
            <button type="button" @click="rows = Math.min(5, rows + 1)" class="text-sm underline mt-2" x-show="rows < 5">+ Add row</button>
        </div>
        <div><label class="block text-xs font-medium uppercase mb-1">Notes</label><textarea name="notes" rows="2" class="w-full border px-3 py-2 text-sm">{{ old('notes') }}</textarea></div>
        <div class="flex gap-2"><button class="px-4 py-2 bg-gray-900 text-white text-sm">Create transfer</button><a href="{{ route('admin.transfers.index') }}" class="px-4 py-2 border text-sm">Cancel</a></div>
    </form>
</div>
@endsection
