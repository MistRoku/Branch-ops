@extends('components.layouts.guest')

@section('title', 'Login - BranchOps Platform')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-brand-50">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-accent-500 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-brand-900 tracking-wide">BRANCHOPS</h1>
            <p class="text-sm text-brand-600 mt-2">Operations Platform</p>
        </div>

        <!-- Login Card -->
        <div class="bg-brand-100 border border-brand-200 p-6">
            <h2 class="text-lg font-semibold text-brand-900 mb-6">Sign In</h2>

            @if($errors->any())
                <div class="bg-brand-100 border border-danger text-danger p-3 mb-4 text-sm">
                    <ul class="list-none pl-0">
                        @foreach($errors->all() as $error)
                            <li class="mb-1">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-brand-900 mb-2">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="w-full px-3 py-2 border border-brand-300 bg-brand-50 text-sm"
                        value="{{ old('email') }}"
                        required 
                        autofocus
                    >
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-brand-900 mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full px-3 py-2 border border-brand-300 bg-brand-50 text-sm"
                        required
                    >
                </div>

                <button type="submit" class="w-full py-2 bg-brand-900 text-white font-medium text-sm">
                    Sign In
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-brand-200">
                <p class="text-xs text-brand-600 text-center">
                    By signing in, you agree to our<br>
                    <a href="{{ route('terms') }}" class="text-accent-600 underline">Terms of Service</a>
                    and
                    <a href="{{ route('privacy') }}" class="text-accent-600 underline">Privacy Policy</a>
                </p>
            </div>
        </div>

        <!-- Footer Links -->
        <div class="mt-8 text-center">
            <p class="text-xs text-brand-500">
                &copy; {{ date('Y') }} BranchOps Platform. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection
