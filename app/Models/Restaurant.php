<?php

namespace App\Models;

use App\Services\TenantCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    use SoftDeletes;

    private const RESERVED_SLUGS = [
        'login',
        'logout',
        'dashboard',
        'items',
        'orders',
        'api',
        'up',
    ];

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $restaurant): void {
            if (empty($restaurant->slug)) {
                $restaurant->slug = Str::slug($restaurant->name);
            }

            if (in_array(strtolower((string) $restaurant->slug), self::RESERVED_SLUGS, true)) {
                $restaurant->slug = $restaurant->slug.'-restaurant';
            }
        });

        static::saved(function (self $restaurant): void {
            app(TenantCacheService::class)->forgetRestaurant($restaurant);
        });

        static::deleted(function (self $restaurant): void {
            app(TenantCacheService::class)->forgetRestaurant($restaurant);
        });
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $field = $field ?? $this->getRouteKeyName();

        if ($field === 'slug' && is_string($value)) {
            return app(TenantCacheService::class)->findRestaurantBySlug($value);
        }

        if ($field === 'id' && (is_int($value) || ctype_digit((string) $value))) {
            return app(TenantCacheService::class)->findRestaurantById((int) $value);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
