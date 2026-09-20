@extends('components.layouts.admin')
@section('title', 'Notifications')
@section('content')
<div class="bg-white border p-6"><div class="flex justify-between items-center mb-4"><h1 class="text-xl font-semibold">Notifications</h1><form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf @method('PUT')<button class="text-sm underline">Mark all read</button></form></div>
<ul class="divide-y">@forelse($notes as $n)<li class="py-3 text-sm flex justify-between"><span>{{ $n->title }} — {{ $n->message }}</span>@if(!$n->read_at)<form method="POST" action="{{ route('admin.notifications.read', $n) }}">@csrf @method('PUT')<button class="text-xs underline">Mark read</button></form>@endif</li>@empty<li class="py-6 text-sm text-gray-500 text-center">No notifications</li>@endforelse</ul></div>
@endsection
