<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    @vite(['resources/js/app.js'])
    <style>
        /* minimal container styling */
        body {
            background: #e9ecf4
        }
    </style>
</head>

<body>
    <button id="sidebarToggle" type="button"
        class="fixed left-4 top-4 z-50 rounded-lg bg-blue-800 p-3 text-white shadow-lg hover:bg-blue-800">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="h-6 w-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>
    <aside id="sidebar-multi-level-sidebar"
        class="fixed left-0 top-0 z-40 h-screen w-64 -translate-x-full bg-blue-800 text-white transition-transform sm:translate-x-0"
        aria-label="Sidebar">
        <div class="bg-neutral-primary-soft border-default h-full overflow-y-auto border-e px-3 py-20">
            <ul class="space-y-2 font-medium">
                <li>
                    <a href="{{ route('home') }}"
                        class="text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group flex items-center px-2 py-1.5">
                        <svg class="group-hover:text-fg-brand h-5 w-5 transition duration-75" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                        </svg>
                        <span class="ms-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('items.index') }}"
                        class="text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group flex items-center px-2 py-1.5">
                        <svg class="group-hover:text-fg-brand h-5 w-5 transition duration-75" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                        </svg>
                        <span class="ms-3">Items Managment</span>
                    </a>
                </li>
                <button id="orderButton" type="button"
                    class="text-bold text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group flex items-center px-2 py-1.5">
                    <svg class="group-hover:text-fg-brand h-5 w-5 transition duration-75" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                    </svg>
                    <span class="ms-3 font-medium">Orders</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 22" stroke-width="2"
                        stroke="currentColor" class="ms-2 h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <li class="order-list" hidden>
                    <a href="{{ route('orders.index') }}"
                        class="text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group ml-5 flex items-center px-2">
                        <svg class="group-hover:text-fg-brand h-5 w-5 transition duration-75" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                        </svg>
                        <span class="ms-3">Orders List</span>
                    </a>
                </li>
                <li class="order-list pt-1.5" hidden>
                    <a href="{{ route('orders.create') }}"
                        class="text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group ml-5 flex items-center px-2">
                        <svg class="group-hover:text-fg-brand h-5 w-5 transition duration-75" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                        </svg>
                        <span class="ms-3">Orders create</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="fixed bottom-4 m-1 mx-5" style="width: 13rem;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-blue-800 p-3 text-white shadow-lg">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <div class="container mx-auto py-8">
        <div class="mx-auto max-w-3xl">
            @if (session('success'))
                <div class="mb-4 rounded bg-green-100 p-3 text-green-800" id="success_message">
                    {{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded bg-red-100 p-3 text-red-800" id="error_message">{{ session('error') }}</div>
            @endif
            @yield('content')
            @yield('scripts')
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.getElementById('success_message').remove();
            document.getElementById('error_message').remove();
        }, 5000);

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sideBar = document.getElementById('sidebar-multi-level-sidebar');

            if (sideBar.hidden == true) {
                sideBar.hidden = false
            } else {
                sideBar.hidden = true
            }
        });

        const orderListBtn = document.getElementById('orderButton');

        function openOrderList() {
            const orderList = document.querySelectorAll('.order-list');
            orderList.forEach(element => {
                if (element.hidden) {
                    element.hidden = false;
                } else {
                    element.hidden = true;
                }
            });
        }
        orderListBtn.addEventListener('click', openOrderList);
    });
</script>

</html>
