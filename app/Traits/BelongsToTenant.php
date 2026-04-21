<?php

namespace App\Traits;

use App\Models\Restaurant;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to any model that belongs to a restaurant (tenant).
 *
 * What this trait does automatically:
 *  1. Registers TenantScope as a global scope → every query is filtered.
 *  2. On creating: auto-fills restaurant_id from the current TenantContext
 *     so controllers never have to pass it manually.
 *  3. Adds the belongsTo(Restaurant::class) relationship.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // 1. Enforce tenant isolation on every SELECT/UPDATE/DELETE
        static::addGlobalScope(new TenantScope());

        // 2. Auto-stamp restaurant_id when creating a new row
        static::creating(function (self $model): void {
            if (empty($model->restaurant_id)) {
                $model->restaurant_id = app(TenantContext::class)->id();
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
