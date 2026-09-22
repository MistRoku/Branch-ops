@extends('components.layouts.admin')
@section('title', 'Customers')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6">
    <h1 class="text-xl font-semibold mb-1">Customers</h1>
    <p class="text-sm text-brand-600 mb-4">Attach customers to sales at the till to track repeat business. Every R10 earned banks 1 loyalty point.</p>
    <div class="flex flex-wrap gap-2 mb-4">
        <form method="GET" class="flex gap-2 text-sm">
            <input name="search" value="{{ request('search') }}" placeholder="Name or phone" class="border border-brand-300 bg-brand-50 px-2 py-1" />
            <button class="px-3 py-1 bg-brand-900 text-white">Search</button>
        </form>
    </div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-brand-600 border-b border-brand-200">
            <th class="py-2 pr-4">Name</th><th class="py-2 pr-4">Phone</th><th class="py-2 pr-4">Sales</th><th class="py-2 pr-4">Points</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($customers as $c)
        <tr class="border-b border-brand-100">
            <td class="py-2 pr-4 font-medium">{{ $c->name }}</td>
            <td class="py-2 pr-4">{{ $c->phone ?? '—' }}</td>
            <td class="py-2 pr-4">{{ $c->sales_count }}</td>
            <td class="py-2 pr-4">{{ $c->loyalty_points }}</td>
            <td class="py-2 text-right"><a href="{{ route('admin.customers.show', $c) }}" class="underline">View</a></td>
        </tr>
        @empty
        <tr><td colspan="5" class="py-8 text-center text-brand-500">No customers yet. Add them at the till or below.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $customers->links() }}</div>
    <h2 class="font-semibold mt-6 mb-2">Add customer</h2>
    <form method="POST" action="{{ route('admin.customers.store') }}" class="flex flex-wrap gap-2 text-sm">@csrf
        <input name="name" required placeholder="Full name" class="border border-brand-300 bg-brand-50 px-2 py-1" />
        <input name="phone" placeholder="Phone" class="border border-brand-300 bg-brand-50 px-2 py-1" />
        <input name="email" type="email" placeholder="Email" class="border border-brand-300 bg-brand-50 px-2 py-1" />
        <button class="px-3 py-1 bg-brand-900 text-white">Add</button>
    </form>
</div>
@endsection
