@extends('components.layouts.admin')
@section('title', 'Settings')
@section('content')
<div class="bg-white border p-6 max-w-xl"><h1 class="text-xl font-semibold">Profile Settings</h1>
<form method="POST" action="{{ route('admin.settings.profile') }}" class="space-y-3 mt-4">@csrf @method('PUT')
<input name="name" value="{{ auth()->user()->name }}" class="w-full border px-3 py-2 text-sm"/>
<input name="email" type="email" value="{{ auth()->user()->email }}" class="w-full border px-3 py-2 text-sm"/>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Save</button></form>
<h2 class="font-semibold mt-8 mb-2">Change password</h2>
<form method="POST" action="{{ route('admin.settings.password') }}" class="space-y-3">@csrf @method('PUT')
<input name="current_password" type="password" placeholder="Current" class="w-full border px-3 py-2 text-sm"/>
<input name="password" type="password" placeholder="New (min 8)" class="w-full border px-3 py-2 text-sm"/>
<input name="password_confirmation" type="password" placeholder="Confirm" class="w-full border px-3 py-2 text-sm"/>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Change</button></form></div>
@endsection
