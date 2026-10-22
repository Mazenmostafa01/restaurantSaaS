<?php

namespace App\Services;

use App\DTOs\OrderWriteData;
use App\Models\Item;
use App\Models\Order;
use App\Traits\Calculation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    use Calculation;

    public function createOrder(OrderWriteData $orderData): Order
    {
        $pricing = $this->calculatePricingAndPivot($orderData->itemQuantities);

        return DB::transaction(function () use ($orderData, $pricing): Order {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber($orderData->orderNumberPrefix),
                'price' => $pricing['subTotal'],
                'tax' => $pricing['tax'],
                'net' => $pricing['netTotal'],
                'type' => $orderData->type,
                'note' => $orderData->note,
                'user_id' => $orderData->userId,
                'customer_id' => $orderData->customerId,
            ]);

            $order->items()->attach($pricing['pivotDetails']);

            return $order;
        });
    }

    public function updateOrder(Order $order, OrderWriteData $orderData): Order
    {
        $pricing = $this->calculatePricingAndPivot($orderData->itemQuantities);

        return DB::transaction(function () use ($order, $orderData, $pricing): Order {
            $order->update([
                'price' => $pricing['subTotal'],
                'tax' => $pricing['tax'],
                'net' => $pricing['netTotal'],
                'type' => $orderData->type,
                'note' => $orderData->note,
                'user_id' => $orderData->userId,
                'customer_id' => $orderData->customerId,
            ]);

            $order->items()->sync($pricing['pivotDetails']);

            return $order;
        });
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->items()->detach();
            $order->delete();
        });
    }

    /**
     * @param  array<int, int>  $itemQuantities
     * @return array{subTotal: float, tax: float, netTotal: float, pivotDetails: array<int, array{quantity: int}>}
     */
    private function calculatePricingAndPivot(array $itemQuantities): array
    {
        $items = Item::whereIn('id', array_keys($itemQuantities))->get()->keyBy('id');

        if ($items->count() !== count($itemQuantities)) {
            throw new ModelNotFoundException('One or more ordered items are unavailable.');
        }

        $subTotal = 0.0;
        $pivotDetails = [];

        foreach ($itemQuantities as $itemId => $quantity) {
            $subTotal += $this->subTotal((float) $items[$itemId]->price, $quantity);
            $pivotDetails[$itemId] = ['quantity' => $quantity];
        }

        $tax = $this->tax($subTotal);

        return [
            'subTotal' => $subTotal,
            'tax' => $tax,
            'netTotal' => $subTotal + $tax,
            'pivotDetails' => $pivotDetails,
        ];
    }

    private function generateOrderNumber(string $prefix = ''): string
    {
        return $prefix.strtoupper(uniqid());
    }
}
