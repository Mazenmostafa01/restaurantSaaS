<?php

namespace App\Services;

use App\Models\Restaurant;

/**
 * A request-scoped singleton that holds the currently active tenant (restaurant).
 *
 * Resolved once per HTTP request by SetTenantContext middleware.
 * Read by TenantScope to automatically filter all Eloquent queries.
 *
 * Superadmin: restaurant_id = null on the User → TenantContext stays empty
 *             → TenantScope adds no WHERE clause → sees all restaurants' data.
 */
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
