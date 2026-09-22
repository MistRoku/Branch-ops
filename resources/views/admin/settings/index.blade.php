@extends('components.layouts.admin')
@section('title', 'Settings')
@section('content')
<div class="space-y-6 max-w-xl">
<div class="bg-brand-100 border border-brand-200 p-6"><h1 class="text-xl font-semibold">Profile Settings</h1>
<form method="POST" action="{{ route('admin.settings.profile') }}" class="space-y-3 mt-4">@csrf @method('PUT')
<label class="block text-sm font-medium">Name<input name="name" value="{{ auth()->user()->name }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ auth()->user()->email }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Phone<input name="phone" value="{{ auth()->user()->phone }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<button class="px-4 py-2 bg-brand-900 text-white text-sm">Save</button></form>
<h2 class="font-semibold mt-8 mb-2">Change password</h2>
<form method="POST" action="{{ route('admin.settings.password') }}" class="space-y-3">@csrf @method('PUT')
<input name="current_password" type="password" placeholder="Current" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm"/>
<input name="password" type="password" placeholder="New (min 8)" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm"/>
<input name="password_confirmation" type="password" placeholder="Confirm" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm"/>
<button class="px-4 py-2 bg-brand-900 text-white text-sm">Change</button></form></div>

@if(auth()->user()->isSuperAdmin() || auth()->user()->isBranchManager())
<div class="bg-brand-100 border border-brand-200 p-6"><h2 class="text-xl font-semibold">Branch Operations</h2>
<p class="text-sm text-brand-600 mt-1">Tax rate applies to new sales at this branch. Receipt header and footer print on every customer receipt.</p>
<form method="POST" action="{{ route('admin.settings.branch') }}" class="space-y-3 mt-4">@csrf @method('PUT')
<label class="block text-sm font-medium">Branch
<select name="branch_id" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1">
@foreach($branches as $b)
<option value="{{ $b->id }}">{{ $b->name }}</option>
@endforeach
@if($branch)
<option value="{{ $branch->id }}" selected>{{ $branch->name }} (current)</option>
@endif
</select></label>
<label class="block text-sm font-medium">Default tax rate %<input name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ $branch?->tax_rate ?? 15 }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Phone<input name="phone" value="{{ $branch?->phone }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Address<input name="address" value="{{ $branch?->address }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Receipt header<input name="receipt_header" value="{{ $branch?->receipt_header }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<label class="block text-sm font-medium">Receipt footer<input name="receipt_footer" value="{{ $branch?->receipt_footer }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"/></label>
<button class="px-4 py-2 bg-brand-900 text-white text-sm">Save branch settings</button></form></div>
@endif
</div>
@endsection
