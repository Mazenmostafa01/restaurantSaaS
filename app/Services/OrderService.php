<?php

namespace App\Services;

use App\DTOs\OrderWriteData;
use App\Models\Item;
use App\Models\Order;
use App\Traits\Calculation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    use Calculation;

    public function createOrder(OrderWriteData $orderData): Order
    {
        $pricing = $this->calculatePricingAndPivot($orderData->itemQuantities);

        return DB::transaction(function () use ($orderData, $pricing): Order {
            $order = Order::create([
                'order_number' => $this->generateUniqueOrderNumber($orderData->orderNumberPrefix),
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
        $existingTrashedIds = $order->items()
            ->onlyTrashed()
            ->pluck('items.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $pricing = $this->calculatePricingAndPivot($orderData->itemQuantities, $existingTrashedIds);

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
     * @param  array<int, int>  $allowedTrashedIds
     * @return array{subTotal: float, tax: float, netTotal: float, pivotDetails: array<int, array{quantity: int}>}
     */
    private function calculatePricingAndPivot(array $itemQuantities, array $allowedTrashedIds = []): array
    {
        $requestedIds = array_keys($itemQuantities);
        $items = Item::withTrashed()
            ->whereIn('id', $requestedIds)
            ->get()
            ->filter(static fn (Item $item): bool => ! $item->trashed() || in_array((int) $item->id, $allowedTrashedIds, true))
            ->keyBy('id');

        if ($items->count() !== count($itemQuantities)) {
            throw new ModelNotFoundException('One or more ordered items are unavailable.');
        }

        $subTotal = 0.0;
        $pivotDetails = [];

        foreach ($itemQuantities as $itemId => $quantity) {
            $subTotal += $this->subTotal((float) $items[$itemId]->price, $quantity);
            $pivotDetails[$itemId] = ['quantity' => $quantity];
        }

        $subTotal = round($subTotal, 2);
        $tax = round($this->tax($subTotal), 2);

        return [
            'subTotal' => $subTotal,
            'tax' => $tax,
            'netTotal' => round($subTotal + $tax, 2),
            'pivotDetails' => $pivotDetails,
        ];
    }

    public function generateUniqueOrderNumber(string $prefix = ''): string
    {
        // Bypass of TenantScope is intentional: order_number uniqueness is checked globally across all orders.
        do {
            $candidate = $prefix.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::withoutGlobalScopes()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
