<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => number_format((float) $this->price, 2),
            'price_raw' => (float) $this->price,
            'description' => $this->description,
            'category' => $this->category,
            'images' => $this->attachments->map->url()->all(),
        ];
    }
}
