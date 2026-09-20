@extends('components.layouts.admin')
@section('title', 'New Stock Take')
@section('content')
<div class="max-w-2xl">
    <a href="{{ route('admin.stock-takes.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Back to stock takes</a>
    <h1 class="text-2xl font-semibold mt-2 mb-4">New Stock Take</h1>
    @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 mb-4"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.stock-takes.store') }}" class="bg-white border p-6 space-y-4" x-data="{ rows: 1 }">@csrf
        <div><label class="block text-xs font-medium uppercase mb-1">Branch *</label><select name="branch_id" class="w-full border px-3 py-2 text-sm">@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-medium uppercase mb-1">Notes</label><textarea name="notes" rows="2" class="w-full border px-3 py-2 text-sm">{{ old('notes') }}</textarea></div>
        <div>
            <label class="block text-xs font-medium uppercase mb-1">Counted items (leave unused rows empty)</label>
            <div class="space-y-2">
                @for($i=0;$i<8;$i++)
                <div class="flex gap-2" x-show="rows > {{ $i }}" x-cloak>
                    <select name="items[{{ $i }}][product_id]" class="flex-1 border px-2 py-1 text-sm"><option value="">— product —</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select>
                    <input name="items[{{ $i }}][quantity_counted]" type="number" min="0" placeholder="Counted" class="w-24 border px-2 py-1 text-sm"/>
                </div>
                @endfor
            </div>
            <button type="button" @click="rows = Math.min(8, rows + 1)" class="text-sm underline mt-2" x-show="rows < 8">+ Add row</button>
        </div>
        <div class="flex gap-2"><button class="px-4 py-2 bg-gray-900 text-white text-sm">Open count</button><a href="{{ route('admin.stock-takes.index') }}" class="px-4 py-2 border text-sm">Cancel</a></div>
    </form>
</div>
@endsection
