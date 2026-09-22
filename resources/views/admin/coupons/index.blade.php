@extends('components.layouts.admin')
@section('title', 'Coupons and Vouchers')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Coupons and Vouchers</h1>
        <a href="{{ route('admin.coupons.create') }}" class="px-4 py-2 bg-brand-900 text-white text-sm">New coupon</a>
    </div>
    <p class="text-sm text-brand-600 mb-4">Cashiers enter the code at the POS. Percent coupons reduce the subtotal by a percentage, fixed coupons by a rand amount.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Code</th><th class="py-2 pr-4">Value</th><th class="py-2 pr-4">Min total</th><th class="py-2 pr-4">Used</th><th class="py-2 pr-4">Valid</th><th class="py-2 pr-4">Status</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($coupons as $c)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4 font-semibold">{{ $c->code }}</td>
            <td class="py-2 pr-4">{{ $c->type === 'percent' ? $c->value.'%' : 'R '.number_format($c->value, 2) }}</td>
            <td class="py-2 pr-4">R {{ number_format($c->min_total, 2) }}</td>
            <td class="py-2 pr-4">{{ $c->used_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</td>
            <td class="py-2 pr-4 text-brand-600">{{ $c->starts_at?->format('Y-m-d') ?? '—' }} to {{ $c->ends_at?->format('Y-m-d') ?? '—' }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs text-white {{ $c->is_active ? 'bg-success' : 'bg-brand-400' }}">{{ $c->is_active ? 'Active' : 'Paused' }}</span></td>
            <td class="py-2 text-right whitespace-nowrap">
                <form method="POST" action="{{ route('admin.coupons.toggle', $c) }}" class="inline">@csrf @method('PUT')<button class="underline text-sm">{{ $c->is_active ? 'Pause' : 'Resume' }}</button></form>
                <form method="POST" action="{{ route('admin.coupons.destroy', $c) }}" class="inline ml-2" onsubmit="return confirm('Remove this coupon?')">@csrf @method('DELETE')<button class="underline text-sm text-danger">Remove</button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="py-8 text-center text-brand-500">No coupons yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $coupons->links() }}</div>
</div>
@endsection
