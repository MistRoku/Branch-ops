@extends('components.layouts.admin')
@section('title', 'Branches')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Branches</h1>
        <a href="{{ route('admin.branches.create') }}" class="px-4 py-2 bg-brand-900 text-white text-sm">New branch</a>
    </div>
    <p class="text-sm text-brand-600 mb-4">Deactivating a branch hides it from selectors and blocks its staff at login. Reactivate anytime to restore access.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Branch</th><th class="py-2 pr-4">Code</th><th class="py-2 pr-4">Users</th><th class="py-2 pr-4">Sales</th><th class="py-2 pr-4">Status</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($branches as $b)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4 font-medium">{{ $b->name }}</td>
            <td class="py-2 pr-4">{{ $b->code }}</td>
            <td class="py-2 pr-4">{{ $b->users_count }}</td>
            <td class="py-2 pr-4">{{ $b->sales_count }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs text-white {{ $b->is_active ? 'bg-success' : 'bg-brand-400' }}">{{ $b->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="py-2 text-right whitespace-nowrap">
                <a href="{{ route('admin.branches.edit', $b) }}" class="underline text-sm">Edit</a>
                @if($b->is_active)
                <form method="POST" action="{{ route('admin.branches.deactivate', $b) }}" class="inline ml-2" onsubmit="return confirm('Deactivate {{ addslashes($b->name) }}? Its staff will be blocked until it is reactivated.')">@csrf @method('PUT')<button class="underline text-sm text-danger">Deactivate</button></form>
                @else
                <form method="POST" action="{{ route('admin.branches.activate', $b) }}" class="inline ml-2" onsubmit="return confirm('Activate {{ addslashes($b->name) }}? Its staff will be able to sign in again.')">@csrf @method('PUT')<button class="underline text-sm text-success">Activate</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="py-8 text-center text-brand-500">No branches yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $branches->links() }}</div>
</div>
@endsection
