<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    @vite(['resources/js/app.js'])
    <style>
        body {
            background: #f8fafc;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            overflow: hidden; /* Prevent body scroll, let main area scroll */
        }
        /* Custom scrollbar */
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
    <!-- Top Header Navigation (Always visible at top) -->
    <header class="bg-white shadow-sm border-b border-gray-100 flex-none h-16 relative z-50">
        <div class="w-full h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center">
                <button id="sidebarToggle" type="button" class="p-2 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors">
                    <span class="sr-only">Toggle sidebar</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="ml-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span class="text-xl font-bold text-gray-900">aklaSaaS</span>
                </div>
            </div>
            <!-- Right side elements like user profile avatar can go here -->
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        <!-- Overlay backdrop for mobile only -->
        <div id="sidebarBackdrop" class="absolute inset-0 bg-gray-900 bg-opacity-50 z-30 transition-opacity duration-300 opacity-0 pointer-events-none lg:hidden" aria-hidden="true"></div>

        <!-- Push Sidebar -->
        <!-- On lg: it is translate-x-0 by default. On smaller: -translate-x-full by default. -->
        <aside id="sidebar-drawer" class="absolute lg:static inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 shadow-xl lg:shadow-none transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col flex-none">
            
            <div class="flex items-center justify-between p-4 border-b border-gray-50 lg:hidden">
                <span class="text-lg font-bold text-gray-900 ml-2">Navigation</span>
                <button id="sidebarClose" type="button" class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none transition-colors">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="p-4 flex-grow overflow-y-auto">
                <ul class="space-y-2 font-medium">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-xl hover:bg-blue-50 hover:text-blue-700 transition-all group">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-700 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6M9 10h6" />
                            </svg>
                            <span class="ms-3 font-semibold">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('items.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-xl hover:bg-blue-50 hover:text-blue-700 transition-all group">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-700 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            <span class="ms-3 font-semibold">Items Management</span>
                        </a>
                    </li>
                    <li>
                        <button id="orderButton" type="button" class="flex items-center w-full px-4 py-3 text-gray-700 rounded-xl hover:bg-blue-50 hover:text-blue-700 transition-all group focus:outline-none">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-700 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span class="ms-3 font-semibold flex-1 text-left">Orders</span>
                            <svg id="orderChevron" class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul id="orderDropdown" class="hidden py-2 space-y-1 mt-1 bg-gray-50 rounded-xl mx-2 border border-gray-100">
                            <li>
                                <a href="{{ route('orders.index') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-600 rounded-lg hover:text-blue-700 hover:bg-white transition-all ml-2 group">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 mr-3 group-hover:bg-blue-600"></span> Orders List
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('orders.create') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-600 rounded-lg hover:text-blue-700 hover:bg-white transition-all ml-2 group">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 mr-3 group-hover:bg-blue-600"></span> Create Order
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <div class="p-4 border-t border-gray-100 mt-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 transition-all hover:bg-red-100 hover:text-red-800">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div id="mainContent" class="flex-1 w-full overflow-y-auto transition-all duration-300 bg-gray-50">
            <main class="container mx-auto py-8 px-4 sm:px-6 lg:px-8 max-w-7xl">
                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm" id="success_message">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm" id="error_message">{{ session('error') }}</div>
                @endif
                
                @yield('content')
                @yield('scripts')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto hide messages
            setTimeout(() => {
                const succ = document.getElementById('success_message');
                const err = document.getElementById('error_message');
                if(succ) succ.remove();
                if(err) err.remove();
            }, 5000);

            // Push Slider logic (Facebook/YouTube style)
            const sidebarBtn = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const drawer = document.getElementById('sidebar-drawer');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mainContent = document.getElementById('mainContent');

            // Track state manually for cleaner logic
            let isDesktopOpen = true; // By default lg:translate-x-0

            function toggleSidebar() {
                if (window.innerWidth >= 1024) { // lg breakpoint in Tailwind
                    // Desktop Behavior: Push content
                    isDesktopOpen = !isDesktopOpen;
                    
                    if (isDesktopOpen) {
                        drawer.classList.remove('-translate-x-full');
                        drawer.classList.add('lg:translate-x-0');
                    } else {
                        // To hide it on desktop, we remove the lg:translate-x-0 class
                        // so it falls back to -translate-x-full
                        drawer.classList.remove('lg:translate-x-0');
                        drawer.classList.add('-translate-x-full');
                    }
                } else {
                    // Mobile Behavior: Overlay Drawer
                    const isClosed = drawer.classList.contains('-translate-x-full');
                    if (isClosed) {
                        // Open drawer
                        drawer.classList.remove('-translate-x-full');
                        backdrop.classList.remove('opacity-0', 'pointer-events-none');
                        backdrop.classList.add('opacity-100', 'pointer-events-auto');
                    } else {
                        // Close drawer
                        drawer.classList.add('-translate-x-full');
                        backdrop.classList.remove('opacity-100', 'pointer-events-auto');
                        backdrop.classList.add('opacity-0', 'pointer-events-none');
                    }
                }
            }

            if (sidebarBtn) sidebarBtn.addEventListener('click', toggleSidebar);
            if (sidebarClose) sidebarClose.addEventListener('click', toggleSidebar);
            if (backdrop) backdrop.addEventListener('click', toggleSidebar);

            // Handle window resize cleanly
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

            // Order dropdown toggle
            const orderListBtn = document.getElementById('orderButton');
            const orderDropdown = document.getElementById('orderDropdown');
            const orderChevron = document.getElementById('orderChevron');

            if (orderListBtn && orderDropdown) {
                orderListBtn.addEventListener('click', function() {
                    if (orderDropdown.classList.contains('hidden')) {
                        orderDropdown.classList.remove('hidden');
                        if(orderChevron) orderChevron.classList.add('rotate-180');
                    } else {
                        orderDropdown.classList.add('hidden');
                        if(orderChevron) orderChevron.classList.remove('rotate-180');
                    }
                });
            }
        });
    </script>
</body>
</html>
