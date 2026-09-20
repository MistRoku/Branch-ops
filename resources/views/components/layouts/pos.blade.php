<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'POS Terminal - BranchOps')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-50 text-slate-900 h-screen overflow-hidden" x-data="connectionStatus">
    <div class="h-full flex flex-col">
        <!-- Header Bar (No Sidebar) -->
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <!-- Brand -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">B</span>
                    </div>
                    <div>
                        <h1 class="font-semibold text-slate-900 leading-tight">BranchOps POS</h1>
                        <p class="text-xs text-gray-500">{{ auth()->user()->branch->name ?? 'Main Branch' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
                <!-- Back Office Button -->
                @if(auth()->user()?->role === 'admin' || auth()->user()?->role === 'manager')
                <a href="{{ route('admin.dashboard') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Back Office
                </a>
                @endif
                
                <!-- User Menu -->
                <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->role }}</p>
                    </div>
                    
                    <!-- Logout Form -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 hover:text-red-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-hidden">
            @yield('content')
        </main>

        <!-- Connection Status Indicator -->
        <div id="connection-status" class="fixed bottom-4 right-4 px-3 py-1.5 text-xs font-medium hidden"
             :class="connectionStatus === 'Connected' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'">
            <span class="inline-block w-2 h-2 mr-2" :class="connectionStatus === 'Connected' ? 'bg-green-600' : 'bg-red-600'"></span>
            <span x-text="connectionStatus"></span>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
