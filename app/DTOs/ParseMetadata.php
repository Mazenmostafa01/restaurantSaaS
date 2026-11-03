<?php

namespace App\DTOs;

readonly class ParseMetadata
{
    /**
     * @param  array<int, string>  $notes
     * @param  array<string, array{
     *     items: array<int, array{
     *         id: int,
     *         name: string,
     *         quantity: int,
     *         unit_price: float,
     *         subtotal: float
     *     }>,
     *     subtotal: float
     * }>  $splits
     */
    public function __construct(
        public array $notes = [],
        public array $splits = [],
        public float $confidenceScore = 1.0,
        public string $languageDetected = 'unknown',
    ) {}
}
