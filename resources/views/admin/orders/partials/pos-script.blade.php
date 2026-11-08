<script>
document.addEventListener('DOMContentLoaded', function () {
    const configEl = document.getElementById('pos-config');
    if (!configEl) return;

    let config = {};
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch (e) {
        console.error('Failed to parse POS configuration:', e);
        return;
    }

    const itemsData = config.items || {};
    const originalSavedTicket = Object.assign({}, config.originalSavedTicket || {});
    let ticket = Object.assign({}, config.initialTicket || {});
    const submitVerb = config.submitVerb || 'Place Order';

    // ----------------------------------------------------
    // DOM Elements
    // ----------------------------------------------------
    const ticketItemsList = document.getElementById('ticketItemsList');
    const emptyTicketState = document.getElementById('emptyTicketState');
    const hiddenItemsContainer = document.getElementById('hiddenItemsContainer');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryTax = document.getElementById('summaryTax');
    const summaryTotal = document.getElementById('summaryTotal');
    const submitOrderBtn = document.getElementById('submitOrderBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const ticketCountBadge = document.getElementById('ticketCountBadge');
    const clearTicketBtn = document.getElementById('clearTicketBtn');
    const resetOrderBtn = document.getElementById('resetOrderBtn');
    const menuSearch = document.getElementById('menuSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const noResultsMsg = document.getElementById('noResultsMsg');
    const orderTypeInput = document.getElementById('order_type_input');
    const customerRequiredTag = document.getElementById('customerRequiredTag');

    // Customer Selection & Autocomplete Elements
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

    // Quick Customer Modal Elements
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

    const orderTypeBtns = document.querySelectorAll('.order-type-btn');
    const orderForm = document.getElementById('orderForm');

    // ----------------------------------------------------
    // Helpers
    // ----------------------------------------------------
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ----------------------------------------------------
    // Order Type Management
    // ----------------------------------------------------
    function setOrderType(type) {
        if (!orderTypeInput) return;
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

    if (orderTypeInput) {
        setOrderType(orderTypeInput.value || 'take_away');
    }

    // ----------------------------------------------------
    // Customer Selection State
    // ----------------------------------------------------
    function setSelectedCustomer(cust) {
        if (!customerIdInput) return;

        if (!cust || !cust.id) {
            customerIdInput.value = '';
            selectedCustomerCard?.classList.add('hidden');
            if (selectedCustomerName) selectedCustomerName.textContent = '';
            if (selectedCustomerPhone) selectedCustomerPhone.textContent = '';
            if (selectedCustomerAddress) selectedCustomerAddress.textContent = '';
            selectedCustomerAddressWrapper?.classList.add('hidden');
            return;
        }

        customerIdInput.value = cust.id;
        customerSearchInput?.classList.remove('ring-2', 'ring-red-500');
        if (selectedCustomerName) selectedCustomerName.textContent = cust.name;
        if (selectedCustomerPhone) selectedCustomerPhone.textContent = cust.phone_number || '';
        if (cust.address && cust.address.trim() !== '') {
            if (selectedCustomerAddress) selectedCustomerAddress.textContent = cust.address.trim();
            selectedCustomerAddressWrapper?.classList.remove('hidden');
        } else {
            if (selectedCustomerAddress) selectedCustomerAddress.textContent = '';
            selectedCustomerAddressWrapper?.classList.add('hidden');
        }
        selectedCustomerCard?.classList.remove('hidden');
        customerResultsDropdown?.classList.add('hidden');
        if (customerSearchInput) customerSearchInput.value = '';
    }

    clearSelectedCustomerBtn?.addEventListener('click', () => {
        setSelectedCustomer(null);
        customerSearchInput?.focus();
    });

    // Hydrate initial customer from config
    if (config.initialCustomer) {
        setSelectedCustomer(config.initialCustomer);
    }

    // ----------------------------------------------------
    // Quick Customer Modal
    // ----------------------------------------------------
    function openQuickModal(prefillPhone = '') {
        if (!quickCustomerModal) return;
        quickCustomerErrors?.classList.add('hidden');
        if (quickCustomerErrorsList) quickCustomerErrorsList.innerHTML = '';
        if (prefillPhone) {
            if (quickCustomerPhone) quickCustomerPhone.value = prefillPhone;
            quickCustomerName?.focus();
        } else {
            if (quickCustomerPhone) {
                quickCustomerPhone.value = customerSearchInput ? customerSearchInput.value.trim() : '';
                if (quickCustomerPhone.value) {
                    quickCustomerName?.focus();
                } else {
                    quickCustomerPhone.focus();
                }
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
            name: quickCustomerName ? quickCustomerName.value.trim() : '',
            phone_number: quickCustomerPhone ? quickCustomerPhone.value.trim() : '',
            address: quickCustomerAddress ? quickCustomerAddress.value.trim() : ''
        };

        fetch(config.quickCustomerUrl, {
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

    // ----------------------------------------------------
    // Debounced Customer Live Search
    // ----------------------------------------------------
    let customerSearchTimer = null;
    let customerAbortController = null;

    if (customerSearchInput) {
        customerSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            clearTimeout(customerSearchTimer);

            if (query.length < 4) {
                customerResultsDropdown?.classList.add('hidden');
                if (customerResultsDropdown) customerResultsDropdown.innerHTML = '';
                customerSearchSpinner?.classList.add('hidden');
                return;
            }

            customerSearchTimer = setTimeout(() => {
                if (customerAbortController) {
                    customerAbortController.abort();
                }
                customerAbortController = new AbortController();
                customerSearchSpinner?.classList.remove('hidden');

                fetch(`${config.searchCustomersUrl}?q=${encodeURIComponent(query)}`, {
                    signal: customerAbortController.signal,
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    const customers = data.customers || [];
                    if (!customerResultsDropdown) return;
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
            customerResultsDropdown?.classList.add('hidden');
        }
    });

    // ----------------------------------------------------
    // Menu Search & Category Filtering
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

    if (menuSearch) {
        menuSearch.addEventListener('input', function () {
            searchQuery = this.value.trim().toLowerCase();
            if (searchQuery.length > 0) {
                clearSearchBtn?.classList.remove('hidden');
            } else {
                clearSearchBtn?.classList.add('hidden');
            }
            filterMenu();
        });
    }

    clearSearchBtn?.addEventListener('click', function () {
        if (!menuSearch) return;
        menuSearch.value = '';
        searchQuery = '';
        clearSearchBtn.classList.add('hidden');
        filterMenu();
        menuSearch.focus();
    });

    document.getElementById('resetSearchFilterBtn')?.addEventListener('click', function () {
        if (menuSearch) menuSearch.value = '';
        searchQuery = '';
        clearSearchBtn?.classList.add('hidden');
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
            noResultsMsg?.classList.remove('hidden');
        } else {
            noResultsMsg?.classList.add('hidden');
        }
    }

    // ----------------------------------------------------
    // Ticket State & Item Actions
    // ----------------------------------------------------
    document.querySelectorAll('.item-card').forEach(card => {
        card.addEventListener('click', function () {
            const id = parseInt(this.dataset.id, 10);
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

    // Reset button for Create mode (clear all items)
    if (clearTicketBtn) {
        clearTicketBtn.addEventListener('click', function () {
            if (Object.keys(ticket).length === 0) return;
            if (confirm('Clear all items from this ticket?')) {
                ticket = {};
                renderTicket();
            }
        });
    }

    // Reset button for Edit mode (restore saved order items)
    if (resetOrderBtn) {
        resetOrderBtn.addEventListener('click', function () {
            if (confirm('Discard your changes and restore the saved order items?')) {
                ticket = Object.assign({}, originalSavedTicket);
                renderTicket();
            }
        });
    }

    function syncMenuCardBadges() {
        document.querySelectorAll('.item-card').forEach(card => {
            const id = parseInt(card.dataset.id, 10);
            const badge = card.querySelector('.item-badge');
            if (ticket[id] && ticket[id] > 0) {
                if (badge) {
                    badge.textContent = ticket[id];
                    badge.classList.remove('hidden');
                }
                card.classList.add('border-amber-500', 'bg-amber-50/20');
            } else {
                if (badge) badge.classList.add('hidden');
                card.classList.remove('border-amber-500', 'bg-amber-50/20');
            }
        });
    }

    function createTicketRowElement(item, id, qty, lineTotal) {
        // 1. Hidden inputs for OrderCreateRequest / OrderUpdateRequest validation
        const hiddenSelected = document.createElement('input');
        hiddenSelected.type = 'hidden';
        hiddenSelected.name = `items[${id}][selected]`;
        hiddenSelected.value = 'on';

        const hiddenQuantity = document.createElement('input');
        hiddenQuantity.type = 'hidden';
        hiddenQuantity.name = `items[${id}][quantity]`;
        hiddenQuantity.value = qty;

        hiddenItemsContainer?.appendChild(hiddenSelected);
        hiddenItemsContainer?.appendChild(hiddenQuantity);

        // 2. Visible Ticket Item Row with XSS-safe escapeHtml
        const row = document.createElement('div');
        row.className = 'py-2.5 flex items-center justify-between gap-3 group';
        row.innerHTML = `
            <div class="flex-1 min-w-0">
                <div class="font-bold text-xs text-gray-900 truncate leading-snug">${escapeHtml(item.name)}</div>
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

        row.querySelector('.btn-dec').addEventListener('click', () => updateItemQuantity(id, qty - 1));
        row.querySelector('.btn-inc').addEventListener('click', () => updateItemQuantity(id, qty + 1));
        row.querySelector('.btn-remove').addEventListener('click', () => removeItemFromTicket(id));

        return row;
    }

    function updateSummaryTotals(subtotal, totalCount) {
        const tax = subtotal * 0.14;
        const grandTotal = subtotal + tax;

        if (ticketCountBadge) ticketCountBadge.textContent = `${totalCount} item${totalCount === 1 ? '' : 's'}`;
        if (summarySubtotal) summarySubtotal.textContent = `EGP ${subtotal.toFixed(2)}`;
        if (summaryTax) summaryTax.textContent = `EGP ${tax.toFixed(2)}`;
        if (summaryTotal) summaryTotal.textContent = `EGP ${grandTotal.toFixed(2)}`;
        if (submitBtnText) submitBtnText.textContent = `${submitVerb} • EGP ${grandTotal.toFixed(2)}`;
        if (submitOrderBtn) submitOrderBtn.disabled = false;
    }

    function renderTicket() {
        const itemIds = Object.keys(ticket).map(id => parseInt(id, 10));
        let subtotal = 0;
        let totalCount = 0;

        // Clear ticket list and hidden form inputs
        if (ticketItemsList) ticketItemsList.innerHTML = '';
        if (hiddenItemsContainer) hiddenItemsContainer.innerHTML = '';

        // Sync card badges on menu
        syncMenuCardBadges();

        if (itemIds.length === 0) {
            if (emptyTicketState && ticketItemsList) {
                ticketItemsList.appendChild(emptyTicketState);
            }
            if (submitOrderBtn) submitOrderBtn.disabled = true;
            if (ticketCountBadge) ticketCountBadge.textContent = '0 items';
            if (summarySubtotal) summarySubtotal.textContent = 'EGP 0.00';
            if (summaryTax) summaryTax.textContent = 'EGP 0.00';
            if (summaryTotal) summaryTotal.textContent = 'EGP 0.00';
            if (submitBtnText) submitBtnText.textContent = `${submitVerb} • EGP 0.00`;
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

            const row = createTicketRowElement(item, id, qty, lineTotal);
            ticketItemsList?.appendChild(row);
        });

        // Compute and update financial ledger
        updateSummaryTotals(subtotal, totalCount);
    }

    // ----------------------------------------------------
    // Form Validation Guard
    // ----------------------------------------------------
    function validateCustomerForDelivery() {
        if (orderTypeInput?.value === 'delivery' && !customerIdInput?.value) {
            alert('A customer is required for delivery orders. Please select or quick-add a caller.');
            customerSearchInput?.classList.add('ring-2', 'ring-red-500');
            customerSearchInput?.focus();
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
    // Keyboard Shortcuts
    // ----------------------------------------------------
    document.addEventListener('keydown', function (e) {
        // Press '/' to search menu
        if (e.key === '/' && document.activeElement !== menuSearch && document.activeElement !== document.getElementById('order_note')) {
            e.preventDefault();
            menuSearch?.focus();
            menuSearch?.select();
        }

        // Press 'Escape' to blur search
        if (e.key === 'Escape') {
            if (document.activeElement === menuSearch) {
                menuSearch?.blur();
            }
        }

        // Press 'Ctrl + Enter' or 'Cmd + Enter' to submit order
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (submitOrderBtn && !submitOrderBtn.disabled) {
                e.preventDefault();
                if (validateCustomerForDelivery()) {
                    orderForm?.submit();
                }
            }
        }
    });

    // ----------------------------------------------------
    // Initial Render
    // ----------------------------------------------------
    renderTicket();
});
</script>
