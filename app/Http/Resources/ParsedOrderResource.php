<?php

namespace App\Http\Resources;

use App\DTOs\ParsedOrderData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ParsedOrderData $resource
 */
class ParsedOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ParsedOrderData $data */
        $data = $this->resource;

        return [
            'items' => array_map(static fn (array $item): array => [
                'id' => (int) $item['id'],
                'name' => (string) $item['name'],
                'quantity' => (int) $item['quantity'],
                'price' => number_format((float) $item['unit_price'], 2),
                'subtotal' => number_format((float) $item['subtotal'], 2),
                'note' => $item['note'],
            ], $data->matchedItems),
            'unmatched' => array_map(static fn (array $item): array => [
                'raw_text' => (string) $item['raw_text'],
                'reason' => (string) $item['reason'],
                'candidate_matches' => (array) ($item['candidate_matches'] ?? []),
            ], $data->unmatchedItems),
            'note' => $data->combinedKitchenNote(),
            'subtotal' => number_format($data->subtotal(), 2),
            'tax' => number_format($data->tax(), 2),
            'net' => number_format($data->netTotal(), 2),
            'splits' => $this->when(! empty($data->splits()), $data->splits()),
        ];
    }
}
