<!-- Quick Add Customer Modal -->
<div id="quickCustomerModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden transform transition-all animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="bg-gray-900 px-5 py-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/20 text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold tracking-tight">Quick Add Phone Customer</h3>
                    <p class="text-[11px] text-gray-400">Instant profile for call-in & delivery orders</p>
                </div>
            </div>
            <button type="button" id="closeQuickCustomerModalBtn" class="rounded-lg p-1 text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body & Form -->
        <form id="quickCustomerForm" class="p-5 space-y-4">
            <!-- Error Banner -->
            <div id="quickCustomerErrors" class="hidden rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                <ul class="list-disc list-inside space-y-0.5" id="quickCustomerErrorsList"></ul>
            </div>

            <!-- Phone Number Field -->
            <div>
                <label for="quick_customer_phone" class="block text-xs font-bold text-gray-700 mb-1">
                    Phone Number <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <input type="text" id="quick_customer_phone" name="phone_number" required placeholder="e.g. 01012345678"
                        class="block w-full rounded-xl border-0 py-2 pl-9 pr-3 text-xs text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-amber-500 bg-gray-50/50" />
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Used to identify caller for repeat orders.</p>
            </div>

            <!-- Customer Name Field -->
            <div>
                <label for="quick_customer_name" class="block text-xs font-bold text-gray-700 mb-1">
                    Customer Name <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="text" id="quick_customer_name" name="name" required placeholder="e.g. Ahmed Hassan"
                        class="block w-full rounded-xl border-0 py-2 pl-9 pr-3 text-xs text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-amber-500 bg-gray-50/50" />
                </div>
            </div>

            <!-- Delivery Address Field -->
            <div>
                <label for="quick_customer_address" class="block text-xs font-bold text-gray-700 mb-1">
                    Delivery Address <span class="text-[10px] font-normal text-gray-400">(Recommended for delivery)</span>
                </label>
                <textarea id="quick_customer_address" name="address" rows="2" dir="auto" placeholder="e.g. 15 Garden St, Floor 3, Apt 12, Zamalek"
                    class="block w-full rounded-xl border-0 py-2 px-3 text-xs text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-amber-500 bg-gray-50/50 resize-none"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                <button type="button" id="cancelQuickCustomerBtn"
                    class="rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="saveQuickCustomerBtn"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-extrabold text-gray-950 shadow-sm hover:bg-amber-400 active:scale-98 transition-all disabled:opacity-50">
                    <span id="quickCustomerSpinner" class="hidden">
                        <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span id="saveQuickCustomerBtnText">Save & Select for Ticket</span>
                </button>
            </div>
        </form>
    </div>
</div>
