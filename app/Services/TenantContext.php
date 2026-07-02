<?php

namespace App\Services;

use App\Models\Restaurant;

class TenantContext
{
    private ?Restaurant $current = null;

    public function set(Restaurant $restaurant): void
    {
        $this->current = $restaurant;
    }

    public function get(): ?Restaurant
    {
        return $this->current;
    }

    public function id(): ?int
    {
        return $this->current?->id;
    }

    public function isSet(): bool
    {
        return $this->current !== null;
    }

    public function forget(): void
    {
        $this->current = null;
    }
}
