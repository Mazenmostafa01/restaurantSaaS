<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SaaS Business Owner Portal' }} - aklaSaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: #f8fafc;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            overflow: hidden;
        }
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>

<body class="flex flex-col h-screen">
    <!-- Top Header Navigation -->
    <header class="bg-white shadow-sm border-b border-gray-100 flex-none h-16 relative z-50">
        <div class="w-full h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" type="button" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors">
                    <span class="sr-only">Toggle sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-800 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 tracking-tight">aklaSaaS</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        SaaS Business Owner
                    </span>
                </div>
            </div>

            <!-- Right Profile Info -->
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-3 pl-4 border-l border-gray-200">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
                        {{ strtoupper(substr(auth('business_owner')->user()?->name ?? 'BO', 0, 2)) }}
                    </div>
                    <div class="text-left">
                        <div class="text-xs font-semibold text-gray-900">{{ auth('business_owner')->user()?->name ?? 'Owner' }}</div>
                        <div class="text-xs text-gray-500 font-mono">{{ auth('business_owner')->user()?->email }}</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        <!-- Overlay backdrop for mobile -->
        <div id="sidebarBackdrop" class="absolute inset-0 bg-gray-900 bg-opacity-50 z-30 transition-opacity duration-300 opacity-0 pointer-events-none lg:hidden" aria-hidden="true"></div>

        <!-- Sidebar Drawer -->
        <aside id="sidebar-drawer" class="absolute lg:static inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 shadow-xl lg:shadow-none transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col flex-none">
            <div class="flex items-center justify-between p-4 border-b border-gray-50 lg:hidden">
                <span class="text-base font-bold text-gray-900 ml-2">Platform Navigation</span>
                <button id="sidebarClose" type="button" class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none transition-colors">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="p-4 flex-grow overflow-y-auto">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Platform Management</div>
                <ul class="space-y-1.5 font-medium">
                    <li>
                        <a href="{{ route('saas.restaurants.index') }}" 
                           class="flex items-center px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('saas.restaurants.index') || request()->routeIs('saas.restaurants.edit') ? 'bg-indigo-50 text-indigo-700 font-semibold shadow-sm' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-700' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('saas.restaurants.index') || request()->routeIs('saas.restaurants.edit') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="ms-3">Restaurants Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('saas.restaurants.create') }}" 
                           class="flex items-center px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('saas.restaurants.create') ? 'bg-indigo-50 text-indigo-700 font-semibold shadow-sm' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-700' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('saas.restaurants.create') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600' }} transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="ms-3">Add New Restaurant</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Logout Section -->
            <div class="p-4 border-t border-gray-100 mt-auto">
                <form method="POST" action="{{ route('saas.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 transition-all hover:bg-red-100 hover:text-red-800">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div id="mainContent" class="flex-1 w-full overflow-y-auto transition-all duration-300 bg-gray-50">
            <main class="container mx-auto py-8 px-4 sm:px-6 lg:px-8 max-w-7xl">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm flex items-start gap-3" id="success_message">
                        <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm flex items-start gap-3" id="error_message">
                        <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm font-medium">{{ session('error') }}</div>
                    </div>
                @endif

                @yield('content')
                @yield('scripts')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto hide flash messages
            setTimeout(() => {
                const succ = document.getElementById('success_message');
                const err = document.getElementById('error_message');
                if (succ) succ.remove();
                if (err) err.remove();
            }, 6000);

            // Responsive Sidebar Slider logic
            const sidebarBtn = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const drawer = document.getElementById('sidebar-drawer');
            const backdrop = document.getElementById('sidebarBackdrop');

            let isDesktopOpen = true;

            function toggleSidebar() {
                if (window.innerWidth >= 1024) {
                    isDesktopOpen = !isDesktopOpen;
                    if (isDesktopOpen) {
                        drawer.classList.remove('-translate-x-full');
                        drawer.classList.add('lg:translate-x-0');
                    } else {
                        drawer.classList.remove('lg:translate-x-0');
                        drawer.classList.add('-translate-x-full');
                    }
                } else {
                    const isClosed = drawer.classList.contains('-translate-x-full');
                    if (isClosed) {
                        drawer.classList.remove('-translate-x-full');
                        backdrop.classList.remove('opacity-0', 'pointer-events-none');
                        backdrop.classList.add('opacity-100', 'pointer-events-auto');
                    } else {
                        drawer.classList.add('-translate-x-full');
                        backdrop.classList.remove('opacity-100', 'pointer-events-auto');
                        backdrop.classList.add('opacity-0', 'pointer-events-none');
                    }
                }
            }

            if (sidebarBtn) sidebarBtn.addEventListener('click', toggleSidebar);
            if (sidebarClose) sidebarClose.addEventListener('click', toggleSidebar);
            if (backdrop) backdrop.addEventListener('click', toggleSidebar);

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    backdrop.classList.remove('opacity-100', 'pointer-events-auto');
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                    if (isDesktopOpen) {
                        drawer.classList.add('lg:translate-x-0');
                        drawer.classList.remove('-translate-x-full');
                    }
                } else {
                    if (!backdrop.classList.contains('opacity-100')) {
                        drawer.classList.add('-translate-x-full');
                    }
                }
            });
        });
    </script>
</body>
</html>
