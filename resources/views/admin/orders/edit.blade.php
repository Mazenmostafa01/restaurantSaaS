@extends('welcome')

@section('content')
    @php
        $categoriesList = $categories ?? \App\Enums\ItemCategoryEnum::cases();
    @endphp

    <div class="w-full">
        <!-- POS Header & Top Bar -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-amber-700">
                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Order Revision Mode
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Edit Order #{{ $order->order_number }}</h1>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" id="resetOrderBtn" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 hover:text-amber-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Changes
                </button>
                <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    All Orders
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm" id="validationErrors">
                <div class="flex items-center gap-2 font-bold mb-2">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Please correct the following:
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- LEFT PANE: Menu & Fast-Touch Items Grid (7 cols on lg, 8 cols on xl) -->
            <div class="lg:col-span-7 xl:col-span-8 flex flex-col gap-4">
                <!-- Search and Filter Bar -->
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                        <!-- Instant Search -->
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="menuSearch" placeholder="Search menu by name (press '/' to focus)..." 
                                class="block w-full rounded-xl border-0 py-2.5 pl-9 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-amber-500 bg-gray-50/50" />
                            <button type="button" id="clearSearchBtn" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Category Pills -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none" id="categoryTabs">
                            <button type="button" data-category="all" 
                                class="category-tab active rounded-xl px-3.5 py-2 text-xs font-bold transition-all whitespace-nowrap bg-gray-900 text-white shadow-sm">
                                All (<span id="totalItemsCount">{{ $items->count() }}</span>)
                            </button>
                            @foreach ($categoriesList as $cat)
                                @php
                                    $catValue = is_object($cat) ? $cat->value : $cat;
                                    $catCount = $items->where('category', $catValue)->count();
                                @endphp
                                <button type="button" data-category="{{ $catValue }}" 
                                    class="category-tab rounded-xl px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-all whitespace-nowrap border border-gray-200 bg-white">
                                    {{ ucfirst($catValue) }} ({{ $catCount }})
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Items Touch Grid -->
                <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm min-h-[420px]">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5" id="itemsGrid">
                        @forelse($items as $item)
                            @php
                                $catStr = is_object($item->category) ? $item->category->value : $item->category;
                            @endphp
                            <div class="item-card group relative flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-3.5 shadow-sm transition-all duration-150 select-none hover:border-amber-400 hover:shadow-md active:scale-98 cursor-pointer"
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->name }}"
                                data-price="{{ $item->price }}"
                                data-category="{{ strtolower($catStr) }}">
                                
                                <!-- Quantity badge indicator (active when in ticket) -->
                                <span class="item-badge hidden absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white shadow-md ring-2 ring-white">
                                    0
                                </span>

                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-gray-500">
                                            {{ $catStr }}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2">
                                        {{ $item->name }}
                                    </h4>
                                </div>

                                <div class="mt-3.5 flex items-center justify-between pt-2 border-t border-gray-100">
                                    <span class="font-extrabold text-sm text-gray-900 tabular-nums">
                                        EGP {{ number_format($item->price, 2) }}
                                    </span>
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-12 text-center text-gray-400">
                                No menu items found for this restaurant.
                            </div>
                        @endforelse
                    </div>

                    <!-- No Search Results Found -->
                    <div id="noResultsMsg" class="hidden py-16 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium">No dishes match your search.</p>
                        <button type="button" id="resetSearchFilterBtn" class="mt-2 text-xs font-semibold text-amber-600 hover:underline">
                            Clear filter & show all
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANE: The Sticky Live Ticket Rail (5 cols on lg, 4 cols on xl) -->
            <div class="lg:col-span-5 xl:col-span-4 sticky top-6">
                <form method="POST" action="{{ route('orders.update', $order->id) }}" id="orderForm" class="rounded-2xl border border-gray-200 bg-white shadow-xl overflow-hidden flex flex-col">
                    @csrf
                    @method('PUT')

                    <!-- Ticket Header -->
                    <div class="bg-gray-900 text-white p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="font-bold text-base tracking-tight">Order #{{ $order->order_number }}</h3>
                            </div>
                            <span class="rounded-full bg-gray-800 px-2.5 py-0.5 text-xs font-semibold text-amber-400 border border-gray-700 tabular-nums" id="ticketCountBadge">
                                0 items
                            </span>
                        </div>

                        <!-- Segmented Order Type Control (Take Away / Delivery) -->
                        <style>
                            .order-type-btn {
                                transition: all 0.15s ease-in-out;
                                border-radius: 0.5rem;
                                cursor: pointer;
                            }
                            .order-type-btn.is-active {
                                background-color: #f59e0b !important;
                                color: #111827 !important;
                                font-weight: 800 !important;
                                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
                            }
                            .order-type-btn:not(.is-active) {
                                background-color: transparent !important;
                                color: #9ca3af !important;
                            }
                            .order-type-btn:not(.is-active):hover {
                                background-color: #374151 !important;
                                color: #ffffff !important;
                            }
                        </style>
                        @php
                            $defaultTypeVal = is_object($order->type) ? $order->type->value : (string) $order->type;
                            $currentType = is_string(old('type')) ? old('type') : $defaultTypeVal;
                            $oldCustomerId = old('customer_id', $order->customer_id);
                            $safeCustomerId = is_scalar($oldCustomerId) ? (string) $oldCustomerId : '';
                            $oldNote = old('note', $order->note);
                            $safeNote = is_string($oldNote) ? $oldNote : '';
                        @endphp
                        <div class="grid grid-cols-2 gap-1.5 p-1 bg-gray-800 rounded-xl border border-gray-700">
                            @foreach ($orderType as $type)
                                @php
                                    $typeVal = is_object($type) ? $type->value : $type;
                                    $isDefault = ($currentType === $typeVal);
                                @endphp
                                <button type="button" data-order-type="{{ $typeVal }}"
                                    class="order-type-btn flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-bold {{ $isDefault ? 'is-active' : '' }}">
                                    @if($typeVal === 'take_away')
                                        <span>🥡</span> Take Away
                                    @else
                                        <span>🛵</span> Delivery
                                    @endif
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="type" id="order_type_input" value="{{ $currentType }}">
                    </div>

                    <!-- Customer Picker Container -->
                    <div class="p-4 border-b border-gray-100 bg-amber-50/40">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-700">
                                    Customer
                                </label>
                                <span id="customerRequiredTag" class="hidden text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-red-100 text-red-700">
                                    Required
                                </span>
                            </div>
                            <button type="button" id="openQuickCustomerModalBtn" class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 hover:text-amber-900 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                New Caller
                            </button>
                        </div>

                        <!-- Hidden Customer ID input -->
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ $safeCustomerId }}">

                        <!-- Search Box with Live Floating Results -->
                        <div class="relative" id="customerSearchContainer">
                            <div class="relative">
                                <input type="text" id="customerSearchInput" placeholder="Search phone (min 4 digits, e.g. 0114)..." 
                                    autocomplete="off"
                                    class="block w-full rounded-xl border border-gray-300 py-2 pl-8 pr-8 text-xs text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white shadow-sm" />
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <span id="customerSearchSpinner" class="hidden absolute inset-y-0 right-0 flex items-center pr-2.5 text-xs text-amber-600">
                                    <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </div>

                            <!-- Live Floating Autocomplete Dropdown -->
                            <div id="customerResultsDropdown" class="hidden absolute left-0 right-0 top-full mt-1 z-50 max-h-60 overflow-y-auto rounded-xl bg-white shadow-2xl border border-gray-200 divide-y divide-gray-100">
                            </div>
                        </div>

                        <!-- Selected Customer State Card -->
                        <div id="selectedCustomerCard" class="hidden mt-2.5 p-3 rounded-xl bg-white border border-amber-300 shadow-sm">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 text-xs font-black">
                                        ✓
                                    </span>
                                    <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-extrabold text-gray-900 truncate" id="selectedCustomerName"></span>
                                        <span class="text-[11px] font-bold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/80 tabular-nums shrink-0" id="selectedCustomerPhone"></span>
                                    </div>
                                </div>
                                <button type="button" id="clearSelectedCustomerBtn" class="shrink-0 text-xs font-bold text-gray-400 hover:text-red-600 px-1.5 py-0.5 rounded transition-colors" title="Remove customer">
                                    ✕ Clear
                                </button>
                            </div>
                            <!-- Responsive Address block -->
                            <div id="selectedCustomerAddressWrapper" class="hidden mt-2 pt-2 border-t border-amber-100/80 flex items-start gap-1.5 text-xs text-gray-700 bg-amber-50/50 p-2 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="break-words leading-relaxed flex-1" id="selectedCustomerAddress" dir="auto"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden form inputs container (auto-generated by JS) -->
                    <div id="hiddenItemsContainer"></div>

                    <!-- Ticket Line Items List (Scrollable) -->
                    <div class="p-4 overflow-y-auto max-h-[340px] flex-1 divide-y divide-gray-100" id="ticketItemsList">
                        <!-- Empty State -->
                        <div id="emptyTicketState" class="py-12 text-center text-gray-400">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-600">Ticket is empty</p>
                            <p class="text-xs text-gray-400 mt-0.5">Tap menu items to add them to this order</p>
                        </div>
                    </div>

                    <!-- Special Notes / Instructions -->
                    <div class="px-4 py-3 bg-gray-50/60 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-1">
                            <label for="order_note" class="text-xs font-semibold text-gray-600">Kitchen Instructions</label>
                            <span class="text-[10px] text-gray-400">Optional</span>
                        </div>
                        <input type="text" name="note" id="order_note" value="{{ $safeNote }}" 
                            placeholder="e.g. Extra sauce, no onions, rush order..."
                            class="block w-full rounded-lg border-0 py-1.5 px-2.5 text-xs text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-amber-500 bg-white" />
                    </div>

                    <!-- Financial Ledger & Totals -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="space-y-1.5 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span class="font-bold text-gray-900 tabular-nums" id="summarySubtotal">EGP 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span>VAT / Tax (14%)</span>
                                <span class="font-bold text-gray-900 tabular-nums" id="summaryTax">EGP 0.00</span>
                            </div>
                            <div class="pt-2 border-t border-gray-200 flex justify-between items-baseline">
                                <span class="text-sm font-extrabold text-gray-900">Total Due</span>
                                <span class="text-2xl font-black text-amber-600 tabular-nums" id="summaryTotal">EGP 0.00</span>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <button type="submit" id="submitOrderBtn" disabled
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 py-3.5 px-4 text-sm font-extrabold text-gray-950 shadow-md transition-all hover:bg-amber-400 active:scale-98 disabled:opacity-40 disabled:pointer-events-none disabled:shadow-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span id="submitBtnText">Update Order • EGP 0.00</span>
                        </button>
                        <p class="text-[11px] text-center text-gray-400 mt-2">
                            Shortcut: <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-[10px] font-mono text-gray-600">Ctrl + Enter</kbd> to save
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.orders.partials.quick-customer-modal')

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ----------------------------------------------------
    // 1. STATE MANAGEMENT
    // ----------------------------------------------------
    const itemsData = {};
    @foreach ($items as $item)
        @php
            $catVal = is_object($item->category) ? $item->category->value : $item->category;
        @endphp
        itemsData[{{ $item->id }}] = {
            id: {{ $item->id }},
            name: {{ Js::from($item->name) }},
            price: {{ (float) $item->price }},
            category: {{ Js::from(strtolower($catVal)) }}
        };
    @endforeach

    // Original Saved Order Ticket (for reset functionality)
    const originalSavedTicket = {};
    @foreach ($order->items as $orderItem)
        originalSavedTicket[{{ $orderItem->id }}] = {{ (int) $orderItem->pivot->quantity }};
    @endforeach

    // Active Ticket State: { [itemId]: quantity }
    let ticket = {};

    @if(is_array(old('items')))
        @foreach(old('items') as $oldId => $oldVal)
            @if(is_array($oldVal) && ($oldVal['selected'] ?? null) === 'on' && isset($oldVal['quantity']) && is_scalar($oldVal['quantity']))
                if (itemsData[{{ (int) $oldId }}]) {
                    ticket[{{ (int) $oldId }}] = {{ max(1, (int) $oldVal['quantity']) }};
                }
            @endif
        @endforeach
    @else
        ticket = Object.assign({}, originalSavedTicket);
    @endif

    // DOM Elements
    const itemsGrid = document.getElementById('itemsGrid');
    const ticketItemsList = document.getElementById('ticketItemsList');
    const emptyTicketState = document.getElementById('emptyTicketState');
    const hiddenItemsContainer = document.getElementById('hiddenItemsContainer');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryTax = document.getElementById('summaryTax');
    const summaryTotal = document.getElementById('summaryTotal');
    const submitOrderBtn = document.getElementById('submitOrderBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const ticketCountBadge = document.getElementById('ticketCountBadge');
    const resetOrderBtn = document.getElementById('resetOrderBtn');
    const menuSearch = document.getElementById('menuSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const noResultsMsg = document.getElementById('noResultsMsg');
    const orderTypeInput = document.getElementById('order_type_input');
    const customerSelect = document.getElementById('customer_id');
    const customerRequiredTag = document.getElementById('customerRequiredTag');
    const customerModeHint = document.getElementById('customerModeHint');

    // Customer Selection & Autocomplete State
    const customerIdInput = document.getElementById('customer_id');
    const customerSearchInput = document.getElementById('customerSearchInput');
    const customerSearchSpinner = document.getElementById('customerSearchSpinner');
    const customerResultsDropdown = document.getElementById('customerResultsDropdown');
    const selectedCustomerCard = document.getElementById('selectedCustomerCard');
    const selectedCustomerName = document.getElementById('selectedCustomerName');
    const selectedCustomerPhone = document.getElementById('selectedCustomerPhone');
    const selectedCustomerAddress = document.getElementById('selectedCustomerAddress');
    const selectedCustomerAddressWrapper = document.getElementById('selectedCustomerAddressWrapper');
    const clearSelectedCustomerBtn = document.getElementById('clearSelectedCustomerBtn');
    const openQuickCustomerModalBtn = document.getElementById('openQuickCustomerModalBtn');

    // ----------------------------------------------------
    // 2. ORDER TYPE & CUSTOMER LOGIC
    // ----------------------------------------------------
    const orderTypeBtns = document.querySelectorAll('.order-type-btn');

    function setOrderType(type) {
        orderTypeInput.value = type;
        orderTypeBtns.forEach(btn => {
            if (btn.dataset.orderType === type) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
        });
        if (type === 'delivery') {
            customerRequiredTag?.classList.remove('hidden');
        } else {
            customerRequiredTag?.classList.add('hidden');
            customerSearchInput?.classList.remove('ring-2', 'ring-red-500');
        }
    }

    orderTypeBtns.forEach(btn => {
        btn.addEventListener('click', () => setOrderType(btn.dataset.orderType));
    });

    // Initialize order type UI
    setOrderType(orderTypeInput.value || 'take_away');

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function setSelectedCustomer(cust) {
        if (!cust || !cust.id) {
            customerIdInput.value = '';
            selectedCustomerCard.classList.add('hidden');
            selectedCustomerName.textContent = '';
            selectedCustomerPhone.textContent = '';
            selectedCustomerAddress.textContent = '';
            selectedCustomerAddressWrapper?.classList.add('hidden');
            return;
        }

        customerIdInput.value = cust.id;
        customerSearchInput?.classList.remove('ring-2', 'ring-red-500');
        selectedCustomerName.textContent = cust.name;
        selectedCustomerPhone.textContent = cust.phone_number || '';
        if (cust.address && cust.address.trim() !== '') {
            selectedCustomerAddress.textContent = cust.address.trim();
            selectedCustomerAddressWrapper?.classList.remove('hidden');
        } else {
            selectedCustomerAddress.textContent = '';
            selectedCustomerAddressWrapper?.classList.add('hidden');
        }
        selectedCustomerCard.classList.remove('hidden');
        customerResultsDropdown.classList.add('hidden');
        customerSearchInput.value = '';
    }

    clearSelectedCustomerBtn?.addEventListener('click', () => {
        setSelectedCustomer(null);
        customerSearchInput.focus();
    });

    @php
        $rawOldCustId = old('customer_id');
        $selectedCustomerModel = is_scalar($rawOldCustId) && $rawOldCustId !== ''
            ? ($customers->firstWhere('id', (int) $rawOldCustId) ?? \App\Models\Customer::find((int) $rawOldCustId))
            : $order->customer;
    @endphp
    @if($selectedCustomerModel)
        setSelectedCustomer({
            id: {{ $selectedCustomerModel->id }},
            name: {{ Js::from($selectedCustomerModel->name) }},
            phone_number: {{ Js::from($selectedCustomerModel->phone_number) }},
            address: {{ Js::from($selectedCustomerModel->address) }}
        });
    @endif

    // Quick Customer Modal controls
    const quickCustomerModal = document.getElementById('quickCustomerModal');
    const closeQuickCustomerModalBtn = document.getElementById('closeQuickCustomerModalBtn');
    const cancelQuickCustomerBtn = document.getElementById('cancelQuickCustomerBtn');
    const quickCustomerForm = document.getElementById('quickCustomerForm');
    const quickCustomerErrors = document.getElementById('quickCustomerErrors');
    const quickCustomerErrorsList = document.getElementById('quickCustomerErrorsList');
    const quickCustomerSpinner = document.getElementById('quickCustomerSpinner');
    const saveQuickCustomerBtn = document.getElementById('saveQuickCustomerBtn');
    const quickCustomerPhone = document.getElementById('quick_customer_phone');
    const quickCustomerName = document.getElementById('quick_customer_name');
    const quickCustomerAddress = document.getElementById('quick_customer_address');

    function openQuickModal(prefillPhone = '') {
        if (!quickCustomerModal) return;
        quickCustomerErrors?.classList.add('hidden');
        if (quickCustomerErrorsList) quickCustomerErrorsList.innerHTML = '';
        if (prefillPhone) {
            quickCustomerPhone.value = prefillPhone;
            quickCustomerName.focus();
        } else {
            quickCustomerPhone.value = customerSearchInput ? customerSearchInput.value.trim() : '';
            if (quickCustomerPhone.value) {
                quickCustomerName.focus();
            } else {
                quickCustomerPhone.focus();
            }
        }
        quickCustomerModal.classList.remove('hidden');
    }

    function closeQuickModal() {
        if (!quickCustomerModal) return;
        quickCustomerModal.classList.add('hidden');
        quickCustomerForm?.reset();
        quickCustomerErrors?.classList.add('hidden');
        if (quickCustomerErrorsList) quickCustomerErrorsList.innerHTML = '';
    }

    openQuickCustomerModalBtn?.addEventListener('click', () => openQuickModal());
    closeQuickCustomerModalBtn?.addEventListener('click', closeQuickModal);
    cancelQuickCustomerBtn?.addEventListener('click', closeQuickModal);

    quickCustomerModal?.addEventListener('click', (e) => {
        if (e.target === quickCustomerModal) closeQuickModal();
    });

    quickCustomerForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        quickCustomerErrors?.classList.add('hidden');
        if (quickCustomerErrorsList) quickCustomerErrorsList.innerHTML = '';
        quickCustomerSpinner?.classList.remove('hidden');
        if (saveQuickCustomerBtn) saveQuickCustomerBtn.disabled = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const payload = {
            name: quickCustomerName.value.trim(),
            phone_number: quickCustomerPhone.value.trim(),
            address: quickCustomerAddress.value.trim()
        };

        fetch(`{{ route('orders.quick-customer') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                throw data;
            }
            return data;
        })
        .then(data => {
            const newCust = data.customer;
            setSelectedCustomer(newCust);
            closeQuickModal();
        })
        .catch(err => {
            quickCustomerErrors?.classList.remove('hidden');
            if (err.errors) {
                Object.values(err.errors).forEach(errArr => {
                    errArr.forEach(msg => {
                        const li = document.createElement('li');
                        li.textContent = msg;
                        quickCustomerErrorsList?.appendChild(li);
                    });
                });
            } else {
                const li = document.createElement('li');
                li.textContent = err.message || 'Failed to create customer.';
                quickCustomerErrorsList?.appendChild(li);
            }
        })
        .finally(() => {
            quickCustomerSpinner?.classList.add('hidden');
            if (saveQuickCustomerBtn) saveQuickCustomerBtn.disabled = false;
        });
    });

    // Debounced Customer Live Search (fetches only when >= 4 digits)
    let customerSearchTimer = null;
    let customerAbortController = null;

    if (customerSearchInput) {
        customerSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            clearTimeout(customerSearchTimer);

            if (query.length < 4) {
                customerResultsDropdown.classList.add('hidden');
                customerResultsDropdown.innerHTML = '';
                customerSearchSpinner?.classList.add('hidden');
                return;
            }

            customerSearchTimer = setTimeout(() => {
                if (customerAbortController) {
                    customerAbortController.abort();
                }
                customerAbortController = new AbortController();
                customerSearchSpinner?.classList.remove('hidden');

                fetch(`{{ route('orders.search-customers') }}?q=${encodeURIComponent(query)}`, {
                    signal: customerAbortController.signal,
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    const customers = data.customers || [];
                    customerResultsDropdown.innerHTML = '';

                    if (customers.length > 0) {
                        customers.forEach(c => {
                            const item = document.createElement('div');
                            item.className = 'p-3 hover:bg-amber-50/70 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0 group';
                            const hasAddress = Boolean(c.address && c.address.trim());
                            const addressHtml = hasAddress ? `
                                <div class="mt-1.5 flex items-start gap-1.5 text-[11px] text-gray-600 bg-gray-50/90 group-hover:bg-amber-100/40 p-2 rounded-lg border border-gray-200/60 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="break-words leading-relaxed flex-1 text-gray-700" dir="auto" title="${escapeHtml(c.address)}">${escapeHtml(c.address)}</span>
                                </div>
                            ` : '';

                            item.innerHTML = `
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-gray-900 truncate" title="${escapeHtml(c.name)}">${escapeHtml(c.name)}</span>
                                    <span class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70 tabular-nums shrink-0">
                                        ${escapeHtml(c.phone_number || '')}
                                    </span>
                                </div>
                                ${addressHtml}
                            `;
                            item.addEventListener('click', () => {
                                setSelectedCustomer(c);
                            });
                            customerResultsDropdown.appendChild(item);
                        });
                    } else {
                        const noMatch = document.createElement('div');
                        noMatch.className = 'p-3 text-center';
                        noMatch.innerHTML = `
                            <p class="text-xs text-gray-500">No customer found for "<span class="font-bold text-gray-800">${escapeHtml(query)}</span>"</p>
                            <button type="button" class="quick-add-btn mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-gray-950 hover:bg-amber-400 shadow-sm transition-all">
                                + Quick Add Caller (${escapeHtml(query)})
                            </button>
                        `;
                        noMatch.querySelector('.quick-add-btn').addEventListener('click', () => {
                            openQuickModal(query);
                            customerResultsDropdown.classList.add('hidden');
                        });
                        customerResultsDropdown.appendChild(noMatch);
                    }

                    customerResultsDropdown.classList.remove('hidden');
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Customer search error:', err);
                    }
                })
                .finally(() => {
                    customerSearchSpinner?.classList.add('hidden');
                });
            }, 250);
        });
    }

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        const container = document.getElementById('customerSearchContainer');
        if (container && !container.contains(e.target)) {
            customerResultsDropdown.classList.add('hidden');
        }
    });

    // ----------------------------------------------------
    // 3. MENU SEARCH & CATEGORY FILTERING
    // ----------------------------------------------------
    let activeCategory = 'all';
    let searchQuery = '';

    const categoryTabs = document.querySelectorAll('.category-tab');
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            categoryTabs.forEach(t => {
                t.className = 'category-tab rounded-xl px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-all whitespace-nowrap border border-gray-200 bg-white';
            });
            this.className = 'category-tab active rounded-xl px-3.5 py-2 text-xs font-bold transition-all whitespace-nowrap bg-gray-900 text-white shadow-sm';
            activeCategory = this.dataset.category;
            filterMenu();
        });
    });

    menuSearch.addEventListener('input', function () {
        searchQuery = this.value.trim().toLowerCase();
        if (searchQuery.length > 0) {
            clearSearchBtn.classList.remove('hidden');
        } else {
            clearSearchBtn.classList.add('hidden');
        }
        filterMenu();
    });

    clearSearchBtn.addEventListener('click', function () {
        menuSearch.value = '';
        searchQuery = '';
        clearSearchBtn.classList.add('hidden');
        filterMenu();
        menuSearch.focus();
    });

    document.getElementById('resetSearchFilterBtn')?.addEventListener('click', function () {
        menuSearch.value = '';
        searchQuery = '';
        clearSearchBtn.classList.add('hidden');
        activeCategory = 'all';
        categoryTabs.forEach((t, idx) => {
            if (idx === 0) {
                t.className = 'category-tab active rounded-xl px-3.5 py-2 text-xs font-bold transition-all whitespace-nowrap bg-gray-900 text-white shadow-sm';
            } else {
                t.className = 'category-tab rounded-xl px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-all whitespace-nowrap border border-gray-200 bg-white';
            }
        });
        filterMenu();
    });

    function filterMenu() {
        let visibleCount = 0;
        document.querySelectorAll('.item-card').forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const cat = card.dataset.category;

            const matchesCategory = (activeCategory === 'all' || cat === activeCategory);
            const matchesSearch = (!searchQuery || name.includes(searchQuery));

            if (matchesCategory && matchesSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (visibleCount === 0) {
            noResultsMsg.classList.remove('hidden');
        } else {
            noResultsMsg.classList.add('hidden');
        }
    }

    // ----------------------------------------------------
    // 4. ITEM ADDITION & TICKET CALCULATIONS
    // ----------------------------------------------------
    document.querySelectorAll('.item-card').forEach(card => {
        card.addEventListener('click', function () {
            const id = parseInt(this.dataset.id);
            addItemToTicket(id);
        });
    });

    function addItemToTicket(id) {
        if (!ticket[id]) {
            ticket[id] = 1;
        } else {
            ticket[id]++;
        }
        renderTicket();
    }

    function updateItemQuantity(id, qty) {
        if (qty <= 0) {
            delete ticket[id];
        } else {
            ticket[id] = Math.min(100, qty);
        }
        renderTicket();
    }

    function removeItemFromTicket(id) {
        delete ticket[id];
        renderTicket();
    }

    resetOrderBtn.addEventListener('click', function () {
        if (confirm('Discard your changes and restore the saved order items?')) {
            ticket = Object.assign({}, originalSavedTicket);
            renderTicket();
        }
    });

    function renderTicket() {
        const itemIds = Object.keys(ticket).map(id => parseInt(id));
        let subtotal = 0;
        let totalCount = 0;

        // Clear ticket list and hidden form inputs
        ticketItemsList.innerHTML = '';
        hiddenItemsContainer.innerHTML = '';

        // Update card badges on menu
        document.querySelectorAll('.item-card').forEach(card => {
            const id = parseInt(card.dataset.id);
            const badge = card.querySelector('.item-badge');
            if (ticket[id] && ticket[id] > 0) {
                badge.textContent = ticket[id];
                badge.classList.remove('hidden');
                card.classList.add('border-amber-500', 'bg-amber-50/20');
            } else {
                badge.classList.add('hidden');
                card.classList.remove('border-amber-500', 'bg-amber-50/20');
            }
        });

        if (itemIds.length === 0) {
            ticketItemsList.appendChild(emptyTicketState);
            submitOrderBtn.disabled = true;
            ticketCountBadge.textContent = '0 items';
            summarySubtotal.textContent = 'EGP 0.00';
            summaryTax.textContent = 'EGP 0.00';
            summaryTotal.textContent = 'EGP 0.00';
            submitBtnText.textContent = 'Update Order • EGP 0.00';
            return;
        }

        // Render each active line item in ticket
        itemIds.forEach(id => {
            const item = itemsData[id];
            if (!item) return;

            const qty = ticket[id];
            const lineTotal = item.price * qty;
            subtotal += lineTotal;
            totalCount += qty;

            // 1. Hidden inputs for OrderUpdateRequest validation
            const hiddenSelected = document.createElement('input');
            hiddenSelected.type = 'hidden';
            hiddenSelected.name = `items[${id}][selected]`;
            hiddenSelected.value = 'on';

            const hiddenQuantity = document.createElement('input');
            hiddenQuantity.type = 'hidden';
            hiddenQuantity.name = `items[${id}][quantity]`;
            hiddenQuantity.value = qty;

            hiddenItemsContainer.appendChild(hiddenSelected);
            hiddenItemsContainer.appendChild(hiddenQuantity);

            // 2. Visible Ticket Item Row
            const row = document.createElement('div');
            row.className = 'py-2.5 flex items-center justify-between gap-3 group';
            row.innerHTML = `
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-xs text-gray-900 truncate leading-snug">${item.name}</div>
                    <div class="text-[11px] text-gray-500 tabular-nums">EGP ${item.price.toFixed(2)} each</div>
                </div>

                <!-- Stepper Buttons -->
                <div class="flex items-center gap-1">
                    <button type="button" class="btn-dec flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 bg-gray-50 text-xs font-bold text-gray-700 hover:bg-gray-200 transition-colors">
                        -
                    </button>
                    <span class="w-7 text-center font-bold text-xs text-gray-900 tabular-nums">${qty}</span>
                    <button type="button" class="btn-inc flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 bg-gray-50 text-xs font-bold text-gray-700 hover:bg-gray-200 transition-colors">
                        +
                    </button>
                </div>

                <div class="text-right w-16">
                    <div class="text-xs font-extrabold text-gray-900 tabular-nums">EGP ${lineTotal.toFixed(2)}</div>
                    <button type="button" class="btn-remove text-[10px] text-gray-400 hover:text-red-600 transition-colors">Remove</button>
                </div>
            `;

            // Attach row events
            row.querySelector('.btn-dec').addEventListener('click', () => updateItemQuantity(id, qty - 1));
            row.querySelector('.btn-inc').addEventListener('click', () => updateItemQuantity(id, qty + 1));
            row.querySelector('.btn-remove').addEventListener('click', () => removeItemFromTicket(id));

            ticketItemsList.appendChild(row);
        });

        // 3. Compute Totals (14% VAT flat trait convention)
        const tax = subtotal * 0.14;
        const grandTotal = subtotal + tax;

        ticketCountBadge.textContent = `${totalCount} item${totalCount === 1 ? '' : 's'}`;
        summarySubtotal.textContent = `EGP ${subtotal.toFixed(2)}`;
        summaryTax.textContent = `EGP ${tax.toFixed(2)}`;
        summaryTotal.textContent = `EGP ${grandTotal.toFixed(2)}`;
        submitBtnText.textContent = `Update Order • EGP ${grandTotal.toFixed(2)}`;
        submitOrderBtn.disabled = false;
    }

    const orderForm = document.getElementById('orderForm');
    function validateCustomerForDelivery() {
        if (orderTypeInput.value === 'delivery' && !customerIdInput.value) {
            alert('A customer is required for delivery orders. Please select or quick-add a caller.');
            customerSearchInput.classList.add('ring-2', 'ring-red-500');
            customerSearchInput.focus();
            return false;
        }
        return true;
    }

    orderForm?.addEventListener('submit', function (e) {
        if (!validateCustomerForDelivery()) {
            e.preventDefault();
        }
    });

    // ----------------------------------------------------
    // 5. KEYBOARD SHORTCUTS FOR SPEED
    // ----------------------------------------------------
    document.addEventListener('keydown', function (e) {
        // Press '/' to search menu
        if (e.key === '/' && document.activeElement !== menuSearch && document.activeElement !== document.getElementById('order_note')) {
            e.preventDefault();
            menuSearch.focus();
            menuSearch.select();
        }

        // Press 'Escape' to blur search
        if (e.key === 'Escape') {
            if (document.activeElement === menuSearch) {
                menuSearch.blur();
            }
        }

        // Press 'Ctrl + Enter' to submit order
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (!submitOrderBtn.disabled) {
                e.preventDefault();
                if (validateCustomerForDelivery()) {
                    orderForm.submit();
                }
            }
        }
    });

    // Initial render
    renderTicket();
});
</script>
@endsection
