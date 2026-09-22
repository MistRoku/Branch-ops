@extends('components.layouts.admin')
@section('title', 'Edit User')
@section('content')
<div class="max-w-xl bg-brand-100 border border-brand-200 p-6"><h1 class="text-xl font-semibold mb-4">Edit {{ $user->name }}</h1>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">@csrf @method('PUT')
<label class="block text-sm font-medium">Full name<input name="name" value="{{ old('name', $user->name) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">New password (leave blank to keep)<input name="password" type="password" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Role<select name="role" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1">@foreach(['staff','branch_manager','super_admin'] as $r)<option value="{{ $r }}" @selected($user->role===$r)>{{ $r }}</option>@endforeach</select></label>
<label class="block text-sm font-medium flex-1">Branch<select name="branch_id" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option value="">No branch</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected($user->branch_id==$b->id)>{{ $b->name }}</option>@endforeach</select></label>
</div>
<h2 class="font-semibold pt-2">Identity verification</h2>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Phone<input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">ID number<input name="id_number" value="{{ old('id_number', $user->id_number) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<h2 class="font-semibold pt-2">Emergency contact</h2>
<label class="block text-sm font-medium">Contact name<input name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Contact phone<input name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">Relationship<input name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $user->emergency_contact_relation) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<label class="block text-sm font-medium">Internal notes<input name="notes" value="{{ old('notes', $user->notes) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="text-sm flex gap-2 items-center"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)/> Active</label>
<button class="px-4 py-2 bg-brand-900 text-white text-sm">Save</button></form></div>
@endsection
