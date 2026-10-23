<?php

namespace App\Services;

use App\Models\Restaurant;
use Closure;
use Illuminate\Support\Facades\Cache;

class TenantCacheService
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function menuTtl(): int
    {
        return (int) config('tenant_cache.menu_ttl', 3600);
    }

    public function dashboardTtl(): int
    {
        return (int) config('tenant_cache.dashboard_ttl', 60);
    }

    public function restaurantTtl(): int
    {
        return (int) config('tenant_cache.restaurant_ttl', 600);
    }

    public function rememberMenu(Closure $callback): array
    {
        $tenantId = $this->tenant->id();
        $ttl = $this->menuTtl();

        if ($tenantId === null || $ttl <= 0) {
            return $callback();
        }

        return Cache::remember($this->menuKey($tenantId), $ttl, $callback);
    }

    public function forgetMenu(?int $tenantId = null): void
    {
        $id = $tenantId ?? $this->tenant->id();
        if ($id !== null) {
            Cache::forget($this->menuKey($id));
        }
    }

    public function rememberDashboard(Closure $callback): array
    {
        $tenantId = $this->tenant->id();
        $ttl = $this->dashboardTtl();

        if ($tenantId === null || $ttl <= 0) {
            return $callback();
        }

        return Cache::remember($this->dashboardKey($tenantId), $ttl, $callback);
    }

    public function forgetDashboard(?int $tenantId = null): void
    {
        $id = $tenantId ?? $this->tenant->id();
        if ($id !== null) {
            Cache::forget($this->dashboardKey($id));
        }
    }

    public function findRestaurantById(int $id): ?Restaurant
    {
        $ttl = $this->restaurantTtl();
        $key = $this->restaurantIdKey($id);

        if ($ttl <= 0) {
            return Restaurant::find($id);
        }

        $cached = Cache::get($key);
        if ($cached instanceof Restaurant) {
            return $cached;
        }

        $restaurant = Restaurant::find($id);
        if ($restaurant instanceof Restaurant) {
            Cache::put($key, $restaurant, $ttl);
        }

        return $restaurant;
    }

    public function findRestaurantBySlug(string $slug): ?Restaurant
    {
        $ttl = $this->restaurantTtl();
        $key = $this->restaurantSlugKey($slug);

        if ($ttl <= 0) {
            return Restaurant::where('slug', $slug)->first();
        }

        $cached = Cache::get($key);
        if ($cached instanceof Restaurant) {
            return $cached;
        }

        $restaurant = Restaurant::where('slug', $slug)->first();
        if ($restaurant instanceof Restaurant) {
            Cache::put($key, $restaurant, $ttl);
        }

        return $restaurant;
    }

    public function forgetRestaurant(Restaurant $restaurant): void
    {
        Cache::forget($this->restaurantIdKey((int) $restaurant->id));

        if (! empty($restaurant->slug)) {
            Cache::forget($this->restaurantSlugKey((string) $restaurant->slug));
        }

        $originalSlug = (string) $restaurant->getOriginal('slug');
        if ($originalSlug !== '' && $originalSlug !== (string) $restaurant->slug) {
            Cache::forget($this->restaurantSlugKey($originalSlug));
        }
    }

    private function menuKey(int $tenantId): string
    {
        return "tenant:{$tenantId}:menu";
    }

    private function dashboardKey(int $tenantId): string
    {
        return "tenant:{$tenantId}:dashboard_analytics";
    }

    private function restaurantIdKey(int $id): string
    {
        return "tenant:restaurant:id:{$id}";
    }

    private function restaurantSlugKey(string $slug): string
    {
        return "tenant:restaurant:slug:{$slug}";
    }
}
