@extends('components.layouts.guest')
@section('title', 'Page not found - BranchOps')
@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-brand-100 border border-brand-200 p-8 text-center">
        <p class="text-4xl font-bold text-brand-900">404</p>
        <h1 class="text-lg font-semibold mt-2">Page not found</h1>
        <p class="text-sm text-brand-600 mt-2">The page you are looking for does not exist or was moved.</p>
        <div class="flex gap-2 justify-center mt-6">
            <a href="/pos" class="px-4 py-2 bg-brand-900 text-white text-sm">POS Terminal</a>
            <a href="/login" class="px-4 py-2 border border-brand-300 text-sm">Sign in</a>
        </div>
    </div>
</div>
@endsection
