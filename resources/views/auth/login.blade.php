@extends('components.layouts.guest')

@section('title', 'Login - BranchOps Platform')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-gray-50">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 mb-4">
                <span class="text-white font-bold text-2xl">B</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-wide">BRANCHOPS</h1>
            <p class="text-sm text-gray-600 mt-2">Operations Platform</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-6">Sign In</h2>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 p-3 mb-4 text-sm">
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
                    <label for="email" class="block text-sm font-medium text-slate-900 mb-2">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="w-full px-3 py-2 border border-gray-300 focus:border-blue-600 focus:ring-0 text-sm"
                        value="{{ old('email') }}"
                        required 
                        autofocus
                    >
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-slate-900 mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full px-3 py-2 border border-gray-300 focus:border-blue-600 focus:ring-0 text-sm"
                        required
                    >
                </div>

                <button type="submit" class="w-full py-2 bg-slate-900 text-white font-medium hover:bg-slate-800 transition-colors text-sm">
                    Sign In
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-600 text-center">
                    By signing in, you agree to our<br>
                    <a href="{{ route('terms') }}" class="text-blue-600 hover:underline">Terms of Service</a>
                    and
                    <a href="{{ route('privacy') }}" class="text-blue-600 hover:underline">Privacy Policy</a>
                </p>
            </div>
        </div>

        <!-- Footer Links -->
        <div class="mt-8 text-center">
            <p class="text-xs text-gray-500">
                &copy; {{ date('Y') }} BranchOps Platform. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection
