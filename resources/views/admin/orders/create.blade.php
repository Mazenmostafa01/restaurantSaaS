@extends('welcome')

@section('content')
<div class="max-w-4xl mx-auto mt-10 bg-white p-8 rounded shadow">
    <h2 class="text-3xl font-bold mb-6">Create Order</h2>

    <form method="POST" action="{{ route('orders.store') }}" id="orderForm">
        @csrf

        <!-- Customer Info -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h3 class="text-lg font-semibold mb-4">Customer Info</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="customer_id" class="block text-gray-700 font-semibold mb-2">Customer</label>
                    <select name="customer_id" id="customer_id" class="w-full border rounded px-3 py-2">
                        <option value="">Select Customer</option>
                        <!-- Load customers dynamically or pre-populate -->
                    </select>
                </div>
                <div>
                    <label for="order_type" class="block text-gray-700 font-semibold mb-2">Order Type</label>
                    <select name="type" id="order_type" class="w-full border rounded px-3 py-2">
                        <option value="">Select Type</option>
                        @foreach ($orderType as $type)
                            <option value="{{ $type->value }}" {{ old('type') == $type->value ? 'selected' : '' }}>{{ ucFirst($type->value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Items Selection -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">Order Items</h3>
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
                        @forelse($items as $item)
                            <tr class="item-row" data-item-id="{{ $item->id }}" data-item-price="{{ $item->price }}" data-item-name="{{ $item->name }}">
                                <td class="border border-gray-300 px-4 py-2">
                                    <input type="checkbox" name="items[{{ $item->id }}][selected]" class="item-checkbox mr-2" {{ old("items.$item->id.selected") ? 'checked' : '' }}>
                                    <span class="item-name">{{ $item->name }}</span>
                                </td>
                                <td class="border border-gray-300 px-4 py-2">{{ ucfirst($item->category) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right item-price">EGP{{ number_format($item->price, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2">
                                    <input type="number" name="items[{{ $item->id }}][quantity]" class="item-quantity w-20 border rounded px-2 py-1 text-center" value="0" min="0"
                                     value="{{ old("items.$item->id.quantity", 0) }}" {{ old("items.$item->id.selected") ? '' : 'disabled' }}>
                                </td>
                                <td class="border border-gray-300 px-4 py-2 text-right item-subtotal">EGP0.00</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border border-gray-300 px-4 py-2 text-center text-gray-500">No items available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($errors->any())
            <div id="error-section" class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <h3 class="font-bold mb-2">Please fix the following errors:</h3>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Order Summary -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <div class="flex justify-end gap-8">
                <div>
                    <span class="font-semibold">Subtotal:</span>
                    <span id="subtotal" class="ml-2">EGP0.00</span>
                </div>
                <div>
                    <span class="font-semibold">Tax (14%):</span>
                    <span id="tax" class="ml-2">EGP0.00</span>
                </div>
                <div class="text-lg font-bold">
                    <span>Total:</span>
                    <span id="total" class="ml-2">EGP0.00</span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="mb-6">
            <label for="note" class="block text-gray-700 font-semibold mb-2">Special Instructions</label>
            <textarea name="note" id="note" class="w-full border rounded px-3 py-2" rows="3" placeholder="Add any special requests..." {{ old('note') }}></textarea>
        </div>

        <!-- Submit -->
        <div class="flex gap-4">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Create Order
            </button>
            <a href="{{ route('home') }}" class="px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Cancel
            </a>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
    // Item selection and quantity management
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('.item-row');
            const quantityInput = row.querySelector('.item-quantity');
            
            if (this.checked) {
                quantityInput.disabled = false;
                if (quantityInput.value === '0') {
                    quantityInput.value = '1';
                }
            } else {
                quantityInput.disabled = true;
                quantityInput.value = '0';
            }
            
            updateTotals();
        });
    });

    // Quantity change handler
    document.querySelectorAll('.item-quantity').forEach(input => {
        input.addEventListener('change', updateTotals);
        input.addEventListener('input', updateTotals);
    });

    // Calculate and update totals
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
</script>
@endsection
