@extends('welcome')

@section('content')
    <div class="mx-auto mt-10 max-w-4xl rounded bg-white p-8 shadow">
        <h2 class="mb-6 text-3xl font-bold">Edit Order</h2>

        <form method="POST" action="{{ route('orders.update', $order->id) }}" id="orderForm">
            @csrf
            @method('PUT')

            <!-- Customer Info -->
            <div class="mb-6 rounded bg-gray-50 p-4">
                <h3 class="mb-4 text-lg font-semibold">Customer Info</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="customer_id" class="mb-2 block font-semibold text-gray-700">Customer</label>
                        <select name="customer_id" id="customer_id" class="w-full rounded border px-3 py-2">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="order_type" class="mb-2 block font-semibold text-gray-700">Order Type</label>
                        <select name="type" id="order_type" class="w-full rounded border px-3 py-2">
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
            <div class="mb-6">
                <h3 class="mb-4 text-lg font-semibold">Order Items</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border border-gray-300 px-4 py-2 text-left">Item</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Category</th>
                                <th class="border border-gray-300 px-4 py-2 text-right">Price</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">Quantity</th>
                                <th class="border border-gray-300 px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="items-list">
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
                                <tr class="item-row" data-item-id="{{ $item->id }}"
                                    data-item-price="{{ $item->price }}" data-item-name="{{ $item->name }}">
                                    <td class="border border-gray-300 px-4 py-2">
                                        <input type="checkbox" name="items[{{ $item->id }}][selected]"
                                            class="item-checkbox mr-2"
                                            {{ old("items.$item->id.selected") ? 'checked' : ($hasItem ? 'checked' : '') }}>
                                        <span class="item-name">{{ $item->name }}</span>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">{{ ucfirst($item->category) }}</td>
                                    <td class="item-price border border-gray-300 px-4 py-2 text-right">
                                        EGP{{ number_format($item->price, 2) }}</td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <input type="number" name="items[{{ $item->id }}][quantity]"
                                            class="item-quantity w-20 rounded border px-2 py-1 text-center" min="0"
                                            value="{{ $qty }}"
                                            {{ old("items.$item->id.selected") ? '' : ($hasItem ? '' : 'disabled') }}>
                                    </td>
                                    <td class="item-subtotal border border-gray-300 px-4 py-2 text-right">
                                        EGP{{ number_format($rowSubtotal, 2) }}</td>
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
            <div class="mb-6 rounded bg-gray-50 p-4">
                <div class="flex justify-end gap-8">
                    <div>
                        <span class="font-semibold">Subtotal:</span>
                        <span id="subtotal" class="ml-2">EGP{{ number_format($initialSubtotal, 2) }}</span>
                    </div>
                    <div>
                        <span class="font-semibold">Tax (14%):</span>
                        <span id="tax" class="ml-2">EGP{{ number_format($initialSubtotal * 0.14, 2) }}</span>
                    </div>
                    <div class="text-lg font-bold">
                        <span>Total:</span>
                        <span id="total" class="ml-2">EGP{{ number_format($initialSubtotal * 1.14, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label for="note" class="mb-2 block font-semibold text-gray-700">Special Instructions</label>
                <textarea name="note" id="note" class="w-full rounded border px-3 py-2" rows="3"
                    placeholder="Add any special requests...">{{ old('note', $order->note) }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex gap-4">
                <button type="submit" class="rounded bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                    Update Order
                </button>
                <a href="{{ route('home') }}" class="rounded bg-gray-600 px-6 py-2 text-white hover:bg-gray-700">
                    Cancel
                </a>
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
