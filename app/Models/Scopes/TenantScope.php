<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Eloquent Scope that automatically appends
 *   WHERE `table`.`restaurant_id` = <current_tenant_id>
 * to every query on models that use the BelongsToTenant trait.
 *
 * If no tenant is set (superadmin), the clause is skipped entirely.
 *
 * Performance note:
 *   The composite indexes on (restaurant_id, created_at) and
 *   (restaurant_id, deleted_at) make this WHERE clause use an index seek,
 *   not a full table scan — safe at 2 000+ orders/day per tenant.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('restaurant_id'), $tenantId);
        }
    }
}
