@extends('saas.layouts.app')

@section('content')
    <!-- Page Header -->
    <header class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Restaurants Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Monitor tenant performance, manage subscriptions, and oversee all restaurant storefronts.</p>
        </div>
        <a href="{{ route('saas.restaurants.create') }}" 
           class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-indigo-500/25 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Restaurant
        </a>
    </header>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Restaurants -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Restaurants</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_restaurants'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Platform-wide tenants</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>

        <!-- Active Restaurants -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Restaurants</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['active_restaurants'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Live & taking orders</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Inactive Restaurants -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Inactive Restaurants</p>
                <p class="text-3xl font-bold text-amber-500 mt-2">{{ $stats['inactive_restaurants'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Suspended or drafted</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Platform Sales</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($stats['total_sales'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Cumulative net revenue</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto pb-1 lg:pb-0">
                <a href="{{ route('saas.restaurants.index', ['status' => 'all', 'q' => request('q')]) }}" 
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors whitespace-nowrap {{ ($status ?? 'all') === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    All
                </a>
                <a href="{{ route('saas.restaurants.index', ['status' => 'active', 'q' => request('q')]) }}" 
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors whitespace-nowrap {{ ($status ?? '') === 'active' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    Active
                </a>
                <a href="{{ route('saas.restaurants.index', ['status' => 'inactive', 'q' => request('q')]) }}" 
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors whitespace-nowrap {{ ($status ?? '') === 'inactive' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    Inactive
                </a>
                <a href="{{ route('saas.restaurants.index', ['status' => 'trashed', 'q' => request('q')]) }}" 
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors whitespace-nowrap inline-flex items-center gap-2 {{ ($status ?? '') === 'trashed' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <span>Archived</span>
                    @if (!empty($stats['trashed_restaurants']))
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ ($status ?? '') === 'trashed' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">
                            {{ $stats['trashed_restaurants'] }}
                        </span>
                    @endif
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('saas.restaurants.index') }}" class="flex items-center gap-2 flex-1 lg:max-w-md">
                <input type="hidden" name="status" value="{{ $status ?? 'all' }}" />
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="q" value="{{ request('q', $search ?? '') }}" placeholder="Search by name, slug, email, phone..." 
                        class="block w-full rounded-xl border-0 py-2.5 pl-10 pr-4 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 bg-gray-50/50" />
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                    Search
                </button>
                @if (request()->filled('q'))
                    <a href="{{ route('saas.restaurants.index', ['status' => $status ?? 'all']) }}" 
                       class="inline-flex items-center px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Restaurants Table Card -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-gray-700">Restaurant</th>
                        <th class="px-6 py-4 font-semibold text-gray-700">Slug & Storefront</th>
                        <th class="px-6 py-4 font-semibold text-gray-700">Status</th>
                        <th class="px-6 py-4 font-semibold text-gray-700 text-center">Orders</th>
                        <th class="px-6 py-4 font-semibold text-gray-700">Total Sales</th>
                        <th class="px-6 py-4 font-semibold text-gray-700 text-center">Staff</th>
                        <th class="px-6 py-4 font-semibold text-gray-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($restaurants as $restaurant)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <!-- Restaurant Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($restaurant->logo_path)
                                        <img src="{{ asset('storage/' . $restaurant->logo_path) }}" alt="{{ $restaurant->name }}" class="w-10 h-10 rounded-xl object-cover border border-gray-200 shadow-sm flex-shrink-0" />
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center flex-shrink-0 shadow-sm">
                                            {{ strtoupper(substr($restaurant->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $restaurant->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $restaurant->email ?? 'No email' }}</div>
                                        @if ($restaurant->phone)
                                            <div class="text-xs text-gray-400 font-mono">{{ $restaurant->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Slug & Storefront Link -->
                            <td class="px-6 py-4">
                                <a href="{{ url('/' . $restaurant->slug) }}" target="_blank" 
                                   class="inline-flex items-center gap-1.5 text-xs font-mono text-indigo-600 hover:text-indigo-800 hover:underline bg-indigo-50/70 px-2.5 py-1 rounded-lg border border-indigo-100 transition-colors"
                                   title="Open customer storefront in new tab">
                                    <span>/{{ $restaurant->slug }}</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4">
                                @if ($restaurant->trashed())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                        Archived
                                    </span>
                                @elseif ($restaurant->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <!-- Orders Count -->
                            <td class="px-6 py-4 text-center font-semibold text-gray-800">
                                {{ $restaurant->orders_count ?? 0 }}
                            </td>

                            <!-- Total Sales -->
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                ${{ number_format($restaurant->total_sales ?? 0, 2) }}
                            </td>

                            <!-- Staff Count -->
                            <td class="px-6 py-4 text-center font-medium text-gray-600">
                                {{ $restaurant->users_count ?? 0 }}
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    @if (! $restaurant->trashed())
                                        <!-- Toggle Active / Deactivate Form -->
                                        <form method="POST" action="{{ route('saas.restaurants.toggle-active', $restaurant) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            @if ($restaurant->is_active)
                                                <button type="submit" 
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors"
                                                    title="Deactivate restaurant (hides storefront and pauses staff access)">
                                                    Deactivate
                                                </button>
                                            @else
                                                <button type="submit" 
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg border border-green-200 bg-green-50 text-xs font-semibold text-green-700 hover:bg-green-100 transition-colors"
                                                    title="Activate restaurant (makes storefront live)">
                                                    Activate
                                                </button>
                                            @endif
                                        </form>

                                        <!-- Edit Link -->
                                        <a href="{{ route('saas.restaurants.edit', $restaurant) }}" 
                                           class="inline-flex items-center px-2.5 py-1 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors"
                                           title="Edit restaurant details and manage admin accounts">
                                            Edit
                                        </a>

                                        <!-- Archive (Soft-Delete) Form -->
                                        <form method="POST" action="{{ route('saas.restaurants.destroy', $restaurant) }}" 
                                              onsubmit="return confirm('Archive this restaurant? It will be deactivated and hidden from storefront.');" 
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors"
                                                title="Archive restaurant">
                                                Archive
                                            </button>
                                        </form>
                                    @else
                                        <!-- Restore Form -->
                                        <form method="POST" action="{{ route('saas.restaurants.restore', $restaurant) }}" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg border border-green-200 bg-green-50 text-xs font-semibold text-green-700 hover:bg-green-100 transition-colors"
                                                title="Restore archived restaurant">
                                                Restore
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Permanent Delete Form (enabled only when inactive or archived) -->
                                    <form method="POST" action="{{ route('saas.restaurants.force-destroy', $restaurant) }}" 
                                          onsubmit="return confirm('Permanently delete this restaurant and ALL its orders, items, staff, and customers? This cannot be undone.');" 
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        @if (! $restaurant->is_active)
                                            <button type="submit" 
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors"
                                                title="Permanently delete restaurant and all associated data">
                                                Delete
                                            </button>
                                        @else
                                            <button type="button" disabled 
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg border border-gray-200 bg-gray-100 text-xs font-semibold text-gray-400 cursor-not-allowed"
                                                title="Deactivate restaurant first before permanent deletion">
                                                Delete
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <p class="text-base font-semibold text-gray-900">No restaurants found</p>
                                <p class="text-sm text-gray-500 mt-1">Get started by creating your first restaurant tenant on the platform.</p>
                                <a href="{{ route('saas.restaurants.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors">
                                    Add New Restaurant
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($restaurants->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $restaurants->links() }}
            </div>
        @endif
    </div>
@endsection
