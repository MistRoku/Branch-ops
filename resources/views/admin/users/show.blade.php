@extends('components.layouts.admin')
@section('title', 'User')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-xl"><h1 class="text-xl font-semibold">{{ $user->name }}</h1>
<p class="text-sm text-brand-600">{{ $user->email }} · {{ $user->role }} · {{ $user->branch?->name ?? 'No branch' }} · {{ $user->is_active ? 'Active' : 'Inactive' }}</p>
@if($user->isLocked())<p class="text-sm text-danger mt-2">Locked until {{ $user->locked_until }}</p>@endif
<div class="mt-4 text-sm space-y-1">
<p><span class="text-brand-600">Phone:</span> {{ $user->phone ?? '—' }}</p>
<p><span class="text-brand-600">ID number:</span> {{ $user->id_number ?? '—' }}</p>
<p><span class="text-brand-600">Emergency contact:</span> {{ $user->emergency_contact_name ?? '—' }} @if($user->emergency_contact_phone) ({{ $user->emergency_contact_phone }}@if($user->emergency_contact_relation), {{ $user->emergency_contact_relation }}@endif) @endif</p>
@if($user->notes)<p><span class="text-brand-600">Notes:</span> {{ $user->notes }}</p>@endif
</div>
<a href="{{ route('admin.users.edit', $user) }}" class="text-sm underline mt-4 inline-block">Edit</a></div>
@endsection
