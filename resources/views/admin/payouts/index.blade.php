@extends('components.layouts.admin')
@section('title', 'Payouts')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Payouts</h1>
        <a href="{{ route('admin.payouts.create') }}" class="px-4 py-2 bg-brand-900 text-white text-sm">Log payout</a>
    </div>
    <p class="text-sm text-brand-600 mb-4">Cash paid out of the drawer, for example supplier COD payments or expense reimbursements. Managers approve before payout is marked paid.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Branch</th><th class="py-2 pr-4">Payee</th><th class="py-2 pr-4">Amount</th>
            <th class="py-2 pr-4">Reason</th><th class="py-2 pr-4">Logged by</th><th class="py-2 pr-4">Status</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($payouts as $p)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4">{{ $p->branch?->name }}</td>
            <td class="py-2 pr-4 font-medium">{{ $p->payee }}</td>
            <td class="py-2 pr-4 font-semibold">R {{ number_format($p->amount, 2) }}</td>
            <td class="py-2 pr-4">{{ $p->reason }}</td>
            <td class="py-2 pr-4">{{ $p->user?->name }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs text-white {{ $p->status === 'paid' ? 'bg-success' : ($p->status === 'rejected' ? 'bg-danger' : ($p->status === 'approved' ? 'bg-info' : 'bg-warning')) }}">{{ ucfirst($p->status) }}</span></td>
            <td class="py-2 text-right whitespace-nowrap">
                @if($p->status === 'pending' && (auth()->user()->isSuperAdmin() || auth()->user()->isBranchManager()))
                <form method="POST" action="{{ route('admin.payouts.approve', $p) }}" class="inline">@csrf @method('PUT')<button class="underline text-sm">Approve</button></form>
                <form method="POST" action="{{ route('admin.payouts.reject', $p) }}" class="inline ml-2">@csrf @method('PUT')<button class="underline text-sm text-danger">Reject</button></form>
                @endif
                @if($p->status === 'approved' && (auth()->user()->isSuperAdmin() || auth()->user()->isBranchManager()))
                <form method="POST" action="{{ route('admin.payouts.pay', $p) }}" class="inline ml-2">@csrf @method('PUT')<button class="underline text-sm">Mark paid</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="py-8 text-center text-brand-500">No payouts logged</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $payouts->links() }}</div>
</div>
@endsection
