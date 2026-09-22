@extends('components.layouts.guest')
@section('title', 'Maintenance - BranchOps')
@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-brand-100 border border-brand-200 p-8 text-center">
        <p class="text-4xl font-bold text-brand-900">503</p>
        <h1 class="text-lg font-semibold mt-2">Temporarily unavailable</h1>
        <p class="text-sm text-brand-600 mt-2">The platform is down for brief maintenance. Please check back shortly.</p>
        <div class="flex gap-2 justify-center mt-6">
            <a href="/login" class="px-4 py-2 bg-brand-900 text-white text-sm">Sign in</a>
        </div>
    </div>
</div>
@endsection
