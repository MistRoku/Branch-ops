@extends('components.layouts.admin')
@section('title', 'Specials')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Specials</h1>
        <a href="{{ route('admin.specials.create') }}" class="px-4 py-2 bg-brand-900 text-white text-sm">New special</a>
    </div>
    <p class="text-sm text-brand-600 mb-4">Special prices apply automatically at the POS for the selected branch, or all branches when no branch is set.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Product</th><th class="py-2 pr-4">Branch</th><th class="py-2 pr-4">Special price</th><th class="py-2 pr-4">Valid</th><th class="py-2 pr-4">Status</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($specials as $s)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ $s->product?->name }} <span class="text-brand-500 text-xs">{{ $s->product?->sku }}</span></td>
            <td class="py-2 pr-4">{{ $s->branch?->name ?? 'All branches' }}</td>
            <td class="py-2 pr-4 font-semibold">R {{ number_format($s->special_price, 2) }}</td>
            <td class="py-2 pr-4 text-brand-600">{{ $s->starts_at?->format('Y-m-d') ?? '—' }} to {{ $s->ends_at?->format('Y-m-d') ?? '—' }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs text-white {{ $s->is_active ? 'bg-success' : 'bg-brand-400' }}">{{ $s->is_active ? 'Active' : 'Paused' }}</span></td>
            <td class="py-2 text-right whitespace-nowrap">
                <form method="POST" action="{{ route('admin.specials.toggle', $s) }}" class="inline">@csrf @method('PUT')<button class="underline text-sm">{{ $s->is_active ? 'Pause' : 'Resume' }}</button></form>
                <form method="POST" action="{{ route('admin.specials.destroy', $s) }}" class="inline ml-2" onsubmit="return confirm('Remove this special?')">@csrf @method('DELETE')<button class="underline text-sm text-danger">Remove</button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="py-8 text-center text-brand-500">No specials yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $specials->links() }}</div>
</div>
@endsection
