<?php

namespace App\Traits;

use App\Models\Restaurant;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

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
