<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Validation\Validator;

class OrderUpdateRequest extends OrderCreateRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = $this->input('items', []);

            if (! is_array($items) || $items === []) {
                return;
            }

            $itemIds = array_map('intval', array_keys($items));
            $activeIds = Item::whereIn('id', $itemIds)->pluck('id')->map(static fn ($id): int => (int) $id)->all();

            $order = $this->route('order');
            $existingOrderItemIds = $order instanceof Order
                ? $order->items()->pluck('items.id')->map(static fn ($id): int => (int) $id)->all()
                : [];

            $allowedIds = array_unique(array_merge($activeIds, $existingOrderItemIds));

            if (count(array_diff($itemIds, $allowedIds)) > 0) {
                $validator->errors()->add('items', 'One or more selected items are invalid for this restaurant.');
            }
        });
    }
}
