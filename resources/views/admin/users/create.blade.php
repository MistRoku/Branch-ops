@extends('components.layouts.admin')
@section('title', 'New User')
@section('content')
<div class="max-w-xl bg-brand-100 border border-brand-200 p-6"><h1 class="text-xl font-semibold mb-4">New User</h1>
<form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">@csrf
<label class="block text-sm font-medium">Full name<input name="name" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Email<input name="email" type="email" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Password (min 8)<input name="password" type="password" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Role<select name="role" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option value="staff">Counter staff</option><option value="branch_manager">Branch manager</option><option value="super_admin">Super admin</option></select></label>
<label class="block text-sm font-medium flex-1">Branch<select name="branch_id" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option value="">None (super admin)</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></label>
</div>
<h2 class="font-semibold pt-2">Identity verification</h2>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Phone<input name="phone" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">ID number<input name="id_number" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<h2 class="font-semibold pt-2">Emergency contact</h2>
<label class="block text-sm font-medium">Contact name<input name="emergency_contact_name" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<div class="flex gap-2">
<label class="block text-sm font-medium flex-1">Contact phone<input name="emergency_contact_phone" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium flex-1">Relationship<input name="emergency_contact_relation" placeholder="Spouse, parent" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
</div>
<label class="block text-sm font-medium">Internal notes<input name="notes" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<button class="px-4 py-2 bg-brand-900 text-white text-sm">Create user</button></form></div>
@endsection
