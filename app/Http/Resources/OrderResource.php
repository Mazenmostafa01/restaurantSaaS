<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'price' => number_format((float) $this->price, 2),
            'tax' => number_format((float) $this->tax, 2),
            'net' => number_format((float) $this->net, 2),
            'type' => $this->type,
            'note' => $this->note,
            'created_at' => $this->created_at->toDateTimeString(),
            'items' => $this->items->map(static fn ($menuItem): array => [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => number_format((float) $menuItem->price, 2),
                'quantity' => $menuItem->pivot->quantity,
            ])->all(),
        ];
    }
}
