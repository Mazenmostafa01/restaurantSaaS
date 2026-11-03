<?php

namespace App\DTOs;

use App\Http\Requests\Customer\CustomerOrderStoreRequest;
use App\Http\Requests\OrderCreateRequest;

readonly class OrderWriteData
{
    /**
     * @param  array<int, int>  $itemQuantities  Map of [item_id => quantity]
     */
    public function __construct(
        public string $type,
        public array $itemQuantities,
        public ?string $note = null,
        public ?int $userId = null,
        public ?int $customerId = null,
        public string $orderNumberPrefix = '',
    ) {}

    public static function fromAdminRequest(OrderCreateRequest $request): self
    {
        $validated = $request->validated();
        $quantities = [];

        foreach ($validated['items'] as $itemId => $selectedItem) {
            $quantities[(int) $itemId] = (int) $selectedItem['quantity'];
        }

        return new self(
            type: $validated['type'],
            itemQuantities: $quantities,
            note: $validated['note'] ?? null,
            userId: $request->user()?->id,
            customerId: isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
        );
    }

    public static function fromCustomerRequest(CustomerOrderStoreRequest $request): self
    {
        $validated = $request->validated();
        $quantities = [];

        foreach ($validated['items'] as $itemData) {
            $quantities[(int) $itemData['id']] = (int) $itemData['quantity'];
        }

        return new self(
            type: $validated['type'],
            itemQuantities: $quantities,
            note: $validated['note'] ?? null,
            userId: null,
            customerId: (int) $request->user()->id,
            orderNumberPrefix: 'C-',
        );
    }

    public static function fromParsedOrder(
        ParsedOrderData $parsedOrder,
        ?int $userId = null,
        ?int $customerId = null,
        string $type = 'take_away',
        string $orderNumberPrefix = 'AI-',
    ): self {
        $quantities = [];

        foreach ($parsedOrder->matchedItems as $item) {
            $itemId = (int) $item['id'];
            $quantities[$itemId] = ($quantities[$itemId] ?? 0) + (int) $item['quantity'];
        }

        return new self(
            type: $type,
            itemQuantities: $quantities,
            note: $parsedOrder->combinedKitchenNote(),
            userId: $userId,
            customerId: $customerId,
            orderNumberPrefix: $orderNumberPrefix,
        );
    }
}
