<?php

namespace App\Services;

use App\DTOs\OrderWriteData;
use App\Models\Item;
use App\Models\Order;
use App\Traits\Calculation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use LogicException;

class OrderService
{
    use Calculation;

    public function __construct(
        private readonly OrderSequenceService $sequence,
        private readonly TenantContext $tenant,
    ) {}

    public function createOrder(OrderWriteData $orderData): Order
    {
        $pricing = $this->calculatePricingAndPivot($orderData->itemQuantities);

        $restaurantId = $this->tenant->id();
        if ($restaurantId === null) {
            throw new LogicException('Cannot create an order without an active tenant context.');
        }

        $orderDate = $this->sequence->businessDate();
        $this->sequence->ensureCounterRow($restaurantId, $orderDate);

        return DB::transaction(function () use ($orderData, $pricing, $restaurantId, $orderDate): Order {
            $dailyNumber = $this->sequence->claimNext($restaurantId, $orderDate);

            $order = Order::create([
                'order_date' => $orderDate,
                'daily_number' => $dailyNumber,
                'order_number' => (string) $dailyNumber,
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
        }, attempts: 3);
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
}
