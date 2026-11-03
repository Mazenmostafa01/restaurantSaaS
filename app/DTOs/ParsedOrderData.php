<?php

namespace App\DTOs;

readonly class ParsedOrderData
{
    /**
     * @param  array<int, array{
     *     id: int,
     *     name: string,
     *     quantity: int,
     *     unit_price: float,
     *     subtotal: float,
     *     note: ?string,
     *     person: ?string,
     *     confidence: float
     * }>  $matchedItems
     * @param  array<int, array{
     *     raw_text: string,
     *     reason: string,
     *     candidate_matches: array<int, string>
     * }>  $unmatchedItems
     */
    public function __construct(
        public array $matchedItems,
        public array $unmatchedItems,
        public ParsePricing $pricing,
        public ParseMetadata $metadata = new ParseMetadata,
    ) {}

    public function hasMatchedItems(): bool
    {
        return count($this->matchedItems) > 0;
    }

    public function hasUnmatchedItems(): bool
    {
        return count($this->unmatchedItems) > 0;
    }

    public function subtotal(): float
    {
        return $this->pricing->subtotal;
    }

    public function tax(): float
    {
        return $this->pricing->tax;
    }

    public function netTotal(): float
    {
        return $this->pricing->netTotal;
    }

    /**
     * @return array<int, string>
     */
    public function notes(): array
    {
        return $this->metadata->notes;
    }

    /**
     * @return array<string, array{
     *     items: array<int, array{
     *         id: int,
     *         name: string,
     *         quantity: int,
     *         unit_price: float,
     *         subtotal: float
     *     }>,
     *     subtotal: float
     * }>
     */
    public function splits(): array
    {
        return $this->metadata->splits;
    }

    /**
     * Combine general order notes and per-item notes into a concise single kitchen note.
     * Truncated to 255 chars to match database schema constraints.
     */
    public function combinedKitchenNote(): ?string
    {
        $segments = [];

        foreach ($this->metadata->notes as $note) {
            $trimmed = trim($note);
            if ($trimmed !== '') {
                $segments[] = $trimmed;
            }
        }

        foreach ($this->matchedItems as $item) {
            if (! empty($item['note'])) {
                $itemNote = trim($item['note']);
                $segments[] = "{$item['name']}: {$itemNote}";
            }
        }

        if (empty($segments)) {
            return null;
        }

        return mb_substr(implode(' | ', $segments), 0, 255);
    }
}
