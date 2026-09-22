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
<body class="bg-brand-50 text-brand-900" style="--brand-accent: {{ auth()->user()?->branch?->accent_color ?? '#2563eb' }}" x-data="connectionStatus">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="min-h-screen flex" x-data="{ collapsed: localStorage.getItem('bo-sidebar') === '1' }" x-init="$watch('collapsed', v => localStorage.setItem('bo-sidebar', v ? '1' : '0'))">
        <!-- Sidebar -->
        <aside class="bg-brand-900 text-white flex-shrink-0 flex flex-col h-screen sticky top-0" :class="collapsed ? 'w-16' : 'w-64'" aria-label="Primary">
            <div class="p-4 border-b border-brand-700 flex items-center gap-3">
                <div class="w-9 h-9 themed-bg flex items-center justify-center font-bold shrink-0">B</div>
                <div x-show="!collapsed">
                    <h1 class="text-lg font-semibold tracking-wide leading-none">BRANCHOPS</h1>
                    <p class="text-xs text-brand-400 mt-1">Operations Platform</p>
                </div>
            </div>

            <nav class="bo-nav p-4 flex-1 overflow-y-auto min-h-0" aria-label="Admin sections">
                <ul class="space-y-1">
                    <li>
                        <a href="/pos" title="POS Terminal" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is('pos') ? 'bg-brand-800' : '' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            <span x-show="!collapsed">POS Terminal</span>
                        </a>
                    </li>
                    @if(auth()->user()?->isSuperAdmin() || auth()->user()?->isBranchManager())
                    @php
                        $navItems = [
                            ['/admin/dashboard', 'admin/dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['/admin/products', 'admin/products*', 'Products', 'M20 7l-8-4-8 4m16 0v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0H4'],
                            ['/admin/inventory', 'admin/inventory*', 'Inventory', 'M5 8h14M5 8a2 2 0 100-4h14a2 2 0 100 4M5 8v12a2 2 0 002 2h10a2 2 0 002-2V8'],
                            ['/admin/sales', 'admin/sales*', 'Sales History', 'M9 14l6-6m-5.5.5h.01m4.99 4.99h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
                            ['/admin/transfers', 'admin/transfers*', 'Transfers', 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                            ['/admin/suppliers', 'admin/suppliers*', 'Suppliers', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-8 0m8 0a4 4 0 01-8 0m8 0v.01'],
                            ['/admin/purchase-orders', 'admin/purchase-orders*', 'Purchase Orders', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                            ['/admin/grv', 'admin/grv*', 'GRV Register', 'M9 12h6m-6 4h6M9 8h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z'],
                            ['/admin/specials', 'admin/specials*', 'Specials', 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'],
                            ['/admin/coupons', 'admin/coupons*', 'Coupons', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                            ['/admin/payouts', 'admin/payouts*', 'Payouts', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5h2'],
                            ['/admin/waste', 'admin/waste*', 'Waste Log', 'M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-2-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                            ['/admin/quotes', 'admin/quotes*', 'Quotations', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                            ['/admin/customers', 'admin/customers*', 'Customers', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ['/admin/users', 'admin/users*', 'Users', 'M12 4.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5zM4 20.5a7.5 7.5 0 0115 0'],
                            ['/admin/stock-takes', 'admin/stock-takes*', 'Stock Takes', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                            ['/admin/documents', 'admin/documents*', 'Documents', 'M7 21h10a2 2 0 002-2V9.5a2.5 2.5 0 00-.7-1.75L13.5 3A2.5 2.5 0 0011.75 2.3H7a2 2 0 00-2 2v14.7a2 2 0 002 2z'],
                            ['/admin/notifications', 'admin/notifications*', 'Notifications', 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3C7.7 6.2 6 8.4 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                            ['/admin/reports', 'admin/reports*', 'Reports', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['/admin/audit-logs', 'admin/audit-logs*', 'Audit Logs', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['/admin/settings', 'admin/settings*', 'Settings', 'M10.3 4.3a1 1 0 011.4 0l1.4 1.4a1 1 0 001.4 0l2.2-2.2a1 1 0 011.4 0l1.5 1.5a1 1 0 010 1.4l-2.2 2.2a1 1 0 000 1.4l1.4 1.4a1 1 0 010 1.4l-1.4 1.4a1 1 0 000 1.4l2.2 2.2a1 1 0 010 1.4l-1.5 1.5a1 1 0 01-1.4 0l-2.2-2.2a1 1 0 00-1.4 0l-1.4 1.4a1 1 0 01-1.4 0l-1.4-1.4a1 1 0 00-1.4 0l-2.2 2.2a1 1 0 01-1.4 0l-1.5-1.5a1 1 0 010-1.4l2.2-2.2a1 1 0 000-1.4l-1.4-1.4a1 1 0 010-1.4l1.4-1.4a1 1 0 000-1.4L3.6 9.6a1 1 0 010-1.4l1.5-1.5a1 1 0 011.4 0l2.2 2.2a1 1 0 001.4 0l1.4-1.4a1 1 0 010-1.4L10.3 4.3zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ];
                    @endphp
                    @foreach($navItems as [$href, $pattern, $label, $path])
                    <li>
                        <a href="{{ $href }}" title="{{ $label }}" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-800 {{ request()->is($pattern) ? 'bg-brand-800' : '' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="{{ $path }}"/></svg>
                            <span x-show="!collapsed">{{ $label }}</span>
                        </a>
                    </li>
                    @endforeach
                    @endif
                </ul>
            </nav>

            <div class="p-4 border-t border-brand-700 space-y-1">
                <button @click="collapsed = !collapsed" :title="collapsed ? 'Expand navigation' : 'Collapse navigation'" aria-label="Toggle navigation"
                        class="w-full flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-800">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path x-show="!collapsed" d="M15 19l-7-7 7-7"/><path x-show="collapsed" d="M9 5l7 7-7 7"/></svg>
                    <span x-show="!collapsed" x-text="collapsed ? 'Expand' : 'Collapse'"></span>
                </button>
                <div class="text-xs text-brand-400 mb-2" x-show="!collapsed">
                    Connected: <span x-text="connectionStatus"></span>
                </div>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" title="Logout" class="w-full flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-800">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        <span x-show="!collapsed">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 overflow-auto" tabindex="-1">
            <!-- Top Bar -->
            <header class="bg-brand-100 border-b border-brand-200 px-6 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button @click="collapsed = !collapsed" aria-label="Toggle navigation" class="p-2 border border-brand-300 bg-brand-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                        </button>
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

    @stack('scripts')
</body>
</html>
