@extends('components.layouts.admin')
@section('title', 'Edit User')
@section('content')
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">Edit {{ $user->name }}</h1>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">@csrf @method('PUT')
<input name="name" value="{{ old('name', $user->name) }}" class="w-full border px-3 py-2 text-sm"/>
<input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full border px-3 py-2 text-sm"/>
<input name="password" type="password" placeholder="New password (leave blank)" class="w-full border px-3 py-2 text-sm"/>
<select name="role" class="w-full border px-3 py-2 text-sm">@foreach(['staff','branch_manager','super_admin'] as $r)<option value="{{ $r }}" @selected($user->role===$r)>{{ $r }}</option>@endforeach</select>
<select name="branch_id" class="w-full border px-3 py-2 text-sm"><option value="">No branch</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected($user->branch_id==$b->id)>{{ $b->name }}</option>@endforeach</select>
<label class="text-sm flex gap-2 items-center"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)/> Active</label>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Save</button></form></div>
@endsection
