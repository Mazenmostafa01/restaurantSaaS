@extends('welcome')

@section('content')
    <div class="mx-auto mt-10 max-w-5xl rounded-2xl bg-white p-8 sm:p-12 shadow-xl ring-1 ring-gray-900/5">
        <h2 class="mb-8 text-3xl font-extrabold text-gray-900 tracking-tight">Edit Order #{{ $order->order_number }}</h2>

        <form method="POST" action="{{ route('orders.update', $order->id) }}" id="orderForm">
            @csrf
            @method('PUT')

            <!-- Customer Info -->
            <div class="mb-8 rounded-xl bg-gray-50/50 p-6 ring-1 ring-inset ring-gray-100">
                <h3 class="mb-5 text-lg font-bold text-gray-900">Customer Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="customer_id" class="mb-2 block text-sm font-medium leading-6 text-gray-900">Customer</label>
                        <select name="customer_id" id="customer_id" class="block w-full rounded-lg border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="order_type" class="mb-2 block text-sm font-medium leading-6 text-gray-900">Order Type</label>
                        <select name="type" id="order_type" class="block w-full rounded-lg border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">Select Type</option>
                            @foreach ($orderType as $type)
                                <option value="{{ $type->value }}"
                                    {{ old('type', $order->type) == $type->value ? 'selected' : '' }}>
                                    {{ ucFirst($type->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Items Selection -->
            <div class="mb-8">
                <h3 class="mb-5 text-lg font-bold text-gray-900">Order Items</h3>
                <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="items-list" class="divide-y divide-gray-200 bg-white">
                            @php
                                $initialSubtotal = 0;
                            @endphp
                            @forelse($items as $item)
                                @php
                                    $hasItem = $order->items->contains($item->id);
                                    $pivot = $order->items->firstWhere('id', $item->id);
                                    $qty = old(
                                        "items.$item->id.quantity",
                                        $hasItem && $pivot ? $pivot->pivot->quantity : 0,
                                    );
                                    $rowSubtotal = $qty * $item->price;
                                    if ($hasItem) {
                                        $initialSubtotal += $rowSubtotal;
                                    }
                                @endphp
                                <tr class="item-row hover:bg-gray-50 transition-colors" data-item-id="{{ $item->id }}"
                                    data-item-price="{{ $item->price }}" data-item-name="{{ $item->name }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <input type="checkbox" name="items[{{ $item->id }}][selected]"
                                                class="item-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 mr-3"
                                                {{ old("items.$item->id.selected") ? 'checked' : ($hasItem ? 'checked' : '') }}>
                                            <span class="item-name font-medium text-gray-900">{{ $item->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ ucfirst($item->category) }}</span>
                                    </td>
                                    <td class="item-price px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 font-medium">
                                        EGP{{ number_format($item->price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <input type="number" name="items[{{ $item->id }}][quantity]"
                                            class="item-quantity w-20 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 text-center" min="0"
                                            value="{{ $qty }}"
                                            {{ old("items.$item->id.selected") ? '' : ($hasItem ? '' : 'disabled') }}>
                                    </td>
                                    <td class="item-subtotal px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                                        EGP{{ number_format($rowSubtotal, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border border-gray-300 px-4 py-2 text-center text-gray-500">No
                                        items available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($errors->any())
                <div id="error-section" class="mb-6 rounded border border-red-400 bg-red-100 p-4 text-red-700">
                    <h3 class="mb-2 font-bold">Please fix the following errors:</h3>
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Order Summary -->
            <div class="mb-8 rounded-xl bg-gray-50 p-6 ring-1 ring-inset ring-gray-100">
                <div class="flex flex-col items-end gap-3 text-gray-700">
                    <div class="flex justify-between w-48 text-sm">
                        <span class="font-medium">Subtotal</span>
                        <span id="subtotal" class="font-semibold text-gray-900">EGP{{ number_format($initialSubtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between w-48 text-sm">
                        <span class="font-medium">Tax (14%)</span>
                        <span id="tax" class="font-semibold text-gray-900">EGP{{ number_format($initialSubtotal * 0.14, 2) }}</span>
                    </div>
                    <div class="flex justify-between w-48 text-lg font-bold border-t border-gray-200 pt-3 mt-1">
                        <span>Total</span>
                        <span id="total" class="text-indigo-600">EGP{{ number_format($initialSubtotal * 1.14, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-8">
                <label for="note" class="mb-2 block text-sm font-medium leading-6 text-gray-900">Special Instructions</label>
                <textarea name="note" id="note" class="block w-full rounded-lg border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" rows="3"
                    placeholder="Add any special requests...">{{ old('note', $order->note) }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-x-4 pt-4 border-t border-gray-100">
                <a href="{{ route('orders.index') }}" class="text-sm font-semibold leading-6 text-gray-900 hover:text-gray-700">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 px-8 py-2.5 text-sm font-semibold text-white shadow-md hover:from-indigo-500 hover:to-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                    Update Order
                </button>
            </div>
        </form>
    </div>

@endsection

@section('scripts')
    <script>
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const row = this.closest('.item-row');
                const quantityInput = row.querySelector('.item-quantity');

                if (this.checked) {
                    quantityInput.disabled = false;
                    if (quantityInput.value === '0' || quantityInput.value === '') {
                        quantityInput.value = '1';
                    }
                } else {
                    quantityInput.disabled = true;
                    quantityInput.value = '0';
                }

                updateTotals();
            });
        });

        document.querySelectorAll('.item-quantity').forEach(input => {
            input.addEventListener('change', updateTotals);
            input.addEventListener('input', updateTotals);
        });

        function updateTotals() {
            let subtotal = 0;

            document.querySelectorAll('.item-row').forEach(row => {
                const checkbox = row.querySelector('.item-checkbox');
                const quantity = parseInt(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.dataset.itemPrice);
                const rowSubtotal = quantity * price;

                row.querySelector('.item-subtotal').textContent = 'EGP' + rowSubtotal.toFixed(2);

                if (checkbox.checked) {
                    subtotal += rowSubtotal;
                }
            });

            const tax = subtotal * 0.14;
            const total = subtotal + tax;

            document.getElementById('subtotal').textContent = 'EGP' + subtotal.toFixed(2);
            document.getElementById('tax').textContent = 'EGP' + tax.toFixed(2);
            document.getElementById('total').textContent = 'EGP' + total.toFixed(2);
        }

        updateTotals();
    </script>
@endsection
