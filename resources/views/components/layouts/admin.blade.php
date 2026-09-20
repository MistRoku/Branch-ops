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
<body class="bg-brand-50 text-brand-900" x-data="layoutData()">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-brand-900 text-white flex-shrink-0" aria-label="Primary">
            <div class="p-4 border-b border-brand-700">
                <h1 class="text-lg font-semibold tracking-wide">BRANCHOPS</h1>
                <p class="text-xs text-brand-400 mt-1">Operations Platform</p>
            </div>

            <nav class="p-4" aria-label="Admin sections">
                <ul class="space-y-1">
                    <li>
                        <a href="/pos" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('pos') ? 'bg-brand-800' : '' }}">
                            POS Terminal
                        </a>
                    </li>
                    @if(auth()->user()?->isSuperAdmin() || auth()->user()?->isBranchManager())
                    <li>
                        <a href="/admin/dashboard" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/dashboard') ? 'bg-brand-800' : '' }}">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="/admin/products" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/products*') ? 'bg-brand-800' : '' }}">
                            Products
                        </a>
                    </li>
                    <li>
                        <a href="/admin/inventory" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/inventory*') ? 'bg-brand-800' : '' }}">
                            Inventory
                        </a>
                    </li>
                    <li>
                        <a href="/admin/transfers" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/transfers*') ? 'bg-brand-800' : '' }}">
                            Transfers
                        </a>
                    </li>
                    <li>
                        <a href="/admin/suppliers" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/suppliers*') ? 'bg-brand-800' : '' }}">
                            Suppliers
                        </a>
                    </li>
                    <li>
                        <a href="/admin/purchase-orders" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/purchase-orders*') ? 'bg-brand-800' : '' }}">
                            Purchase Orders
                        </a>
                    </li>
                    <li>
                        <a href="/admin/users" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/users*') ? 'bg-brand-800' : '' }}">
                            Users
                        </a>
                    </li>
                    <li>
                        <a href="/admin/stock-takes" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/stock-takes*') ? 'bg-brand-800' : '' }}">
                            Stock Takes
                        </a>
                    </li>
                    <li>
                        <a href="/admin/documents" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/documents*') ? 'bg-brand-800' : '' }}">
                            Documents
                        </a>
                    </li>
                    <li>
                        <a href="/admin/notifications" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/notifications*') ? 'bg-brand-800' : '' }}">
                            Notifications
                        </a>
                    </li>
                    <li>
                        <a href="/admin/reports" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/reports*') ? 'bg-brand-800' : '' }}">
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="/admin/audit-logs" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/audit-logs*') ? 'bg-brand-800' : '' }}">
                            Audit Logs
                        </a>
                    </li>
                    <li>
                        <a href="/admin/settings" class="block px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('admin/settings*') ? 'bg-brand-800' : '' }}">
                            Settings
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>

            <div class="absolute bottom-0 w-64 p-4 border-t border-brand-700">
                <div class="text-xs text-brand-400 mb-2">
                    Connected: <span x-text="connectionStatus"></span>
                </div>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-brand-800">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 overflow-auto" tabindex="-1">
            <!-- Top Bar -->
            <header class="bg-white border-b border-brand-200 px-6 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        @yield('header-left')
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Global Search -->
                        <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                            <button @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true" aria-label="Open global search" class="p-2 hover:bg-brand-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                            @yield('search-modal')
                        </div>

                        <!-- Notifications -->
                        <div x-data="{ open: false, unread: 0 }" class="relative" @keydown.escape.window="open = false">
                            <button @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true" aria-label="Open notifications" class="p-2 hover:bg-brand-100 relative">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span x-show="unread > 0" class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-none"></span>
                            </button>
                            @yield('notification-dropdown')
                        </div>

                        <!-- User Menu -->
                        <div class="text-sm text-brand-600">
                            {{ auth()->user()->name ?? 'User' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6">
                @if(session('success'))
                    <div class="alert alert-success mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function layoutData() {
            return {
                connectionStatus: 'Connecting...',
                init() {
                    this.checkConnection();
                    window.Echo?.connector?.pusher?.connection.bind('connected', () => {
                        this.connectionStatus = 'Connected';
                    });
                    window.Echo?.connector?.pusher?.connection.bind('disconnected', () => {
                        this.connectionStatus = 'Disconnected';
                    });
                },
                checkConnection() {
                    // Connection status will be updated by Echo events
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
