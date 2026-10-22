<?php

namespace App\Traits;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

trait ScopesValidationToTenant
{
    protected function tenantUnique(string $table, string $column = 'NULL'): Unique
    {
        $rule = Rule::unique($table, $column);
        $tenantId = app(TenantContext::class)->id();

        return $tenantId !== null ? $rule->where('restaurant_id', $tenantId) : $rule;
    }

    protected function tenantExists(string $table, string $column = 'NULL', bool $withoutTrashed = false): Exists
    {
        $rule = Rule::exists($table, $column);
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId !== null) {
            $rule->where('restaurant_id', $tenantId);
        }

        return $withoutTrashed ? $rule->withoutTrashed() : $rule;
    }
}
