@extends('components.layouts.admin')
@section('title', 'Audit Logs')
@section('content')
<div class="bg-white border"><h1 class="text-xl font-semibold p-6">Audit Logs</h1>
<table class="min-w-full divide-y"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs uppercase">When</th><th class="px-6 py-3 text-left text-xs uppercase">User</th><th class="px-6 py-3 text-left text-xs uppercase">Action</th><th class="px-6 py-3 text-left text-xs uppercase">Entity</th><th class="px-6 py-3 text-right text-xs uppercase">View</th></tr></thead>
<tbody class="divide-y">@forelse($logs as $l)<tr><td class="px-6 py-3 text-sm">{{ $l->created_at }}</td><td class="px-6 py-3 text-sm">{{ $l->user?->name }}</td><td class="px-6 py-3 text-sm">{{ $l->action }}</td><td class="px-6 py-3 text-sm">{{ $l->entity_type }} #{{ $l->entity_id }}</td><td class="px-6 py-3 text-right text-sm"><a href="{{ route('admin.audit-logs.show', $l) }}" class="underline">View</a></td></tr>@empty<tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No logs</td></tr>@endforelse</tbody></table>
@if($logs->hasPages())<div class="p-4 border-t">{{ $logs->links() }}</div>@endif</div>
@endsection
