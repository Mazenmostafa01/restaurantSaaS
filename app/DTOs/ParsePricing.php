<?php

namespace App\DTOs;

readonly class ParsePricing
{
    public function __construct(
        public float $subtotal,
        public float $tax,
        public float $netTotal,
    ) {}
}
