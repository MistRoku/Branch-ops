<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'BranchOps Platform')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-brand-50 text-brand-900">
    <div class="min-h-screen flex flex-col">
        <div class="flex-1">
            @yield('content')
        </div>
        <footer class="border-t border-brand-200 bg-brand-100 px-4 py-4">
            <div class="max-w-md mx-auto flex items-center justify-center gap-4 text-xs text-brand-600">
                <span>&copy; {{ date('Y') }} BranchOps Platform</span>
                <span aria-hidden="true">|</span>
                <a href="{{ route('terms') }}" class="underline">Terms of Service</a>
                <span aria-hidden="true">|</span>
                <a href="{{ route('privacy') }}" class="underline">Privacy Policy</a>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
