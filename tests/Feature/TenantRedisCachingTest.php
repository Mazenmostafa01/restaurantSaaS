<?php

namespace Tests\Feature;

use App\DTOs\ItemWriteData;
use App\DTOs\OrderWriteData;
use App\Enums\ItemCategoryEnum;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use App\Services\ItemService;
use App\Services\OrderService;
use App\Services\TenantCacheService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRedisCachingTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin', 'web');

        $this->restaurant = Restaurant::create([
            'name' => 'Redis Bistro',
            'slug' => 'redis-bistro',
            'email' => 'bistro@example.com',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Chef',
            'email' => 'chef@redisbistro.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurant->id,
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_customer_menu_is_cached_and_served_from_cache(): void
    {
        app(TenantContext::class)->set($this->restaurant);

        Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Cached Burger',
            'price' => 150.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'description' => 'Crispy and juicy',
        ]);

        $cacheKey = "tenant:{$this->restaurant->id}:menu";
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));

        // First request: Cache Miss -> populates cache
        $response = $this->getJson("/api/customer/{$this->restaurant->slug}/menu");
        $response->assertOk();
        $this->assertTrue(Cache::has($cacheKey));

        // Second request: served from cache
        $cachedMenu = Cache::get($cacheKey);
        $this->assertIsArray($cachedMenu);
        $this->assertSame('food', $cachedMenu[0]['category']);
        $this->assertSame('Cached Burger', $cachedMenu[0]['items'][0]['name']);
    }

    public function test_item_mutations_invalidate_menu_cache_for_specific_tenant(): void
    {
        $otherRestaurant = Restaurant::create([
            'name' => 'Other Pizza',
            'slug' => 'other-pizza',
            'email' => 'other@example.com',
            'is_active' => true,
        ]);

        app(TenantContext::class)->set($this->restaurant);
        $item = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Old Burger',
            'price' => 120.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $itemService = app(ItemService::class);
        Cache::put("tenant:{$this->restaurant->id}:menu", ['stale data'], 3600);
        Cache::put("tenant:{$otherRestaurant->id}:menu", ['other data'], 3600);

        // Update item -> invalidates this tenant's menu cache
        $itemService->updateItem($item, new ItemWriteData(
            name: 'Updated Burger',
            price: 130.00,
            category: ItemCategoryEnum::FOOD->value,
            description: 'Now with cheese'
        ));

        $this->assertFalse(Cache::has("tenant:{$this->restaurant->id}:menu"));
        $this->assertTrue(Cache::has("tenant:{$otherRestaurant->id}:menu"), 'Other tenant cache must remain untouched');

        // Repopulate and delete item -> invalidates menu cache
        Cache::put("tenant:{$this->restaurant->id}:menu", ['stale data'], 3600);
        $itemService->deleteItem($item);
        $this->assertFalse(Cache::has("tenant:{$this->restaurant->id}:menu"));
    }

    public function test_dashboard_analytics_is_cached_and_served_by_ttl_without_order_thrashing(): void
    {
        app(TenantContext::class)->set($this->restaurant);

        $item = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Steak',
            'price' => 200.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $customer = Customer::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'John Diner',
            'email' => 'john@test.com',
            'phone_number' => '01011112222',
            'password' => bcrypt('password'),
            'address' => 'Cairo',
        ]);

        $analytics = app(DashboardAnalyticsService::class);
        $orderService = app(OrderService::class);

        $dashKey = "tenant:{$this->restaurant->id}:dashboard_analytics";
        Cache::forget($dashKey);

        // Initial compute caches data
        $data1 = $analytics->getDashboardData();
        $this->assertTrue(Cache::has($dashKey));
        $this->assertSame(0, $data1['orders']->total_orders);

        // Create order via OrderService: should NOT thrash the dashboard cache during peak traffic
        $order = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            note: 'Extra sauce',
            userId: $this->admin->id,
            customerId: $customer->id,
            itemQuantities: [$item->id => 2],
            orderNumberPrefix: 'RB-'
        ));

        $this->assertTrue(Cache::has($dashKey), 'Order creation should NOT invalidate dashboard cache; rely on TTL');
        $this->assertStringStartsWith('RB-', $order->order_number);

        // Once TTL expires or cache is refreshed, new order data is visible
        Cache::forget($dashKey);
        $data2 = $analytics->getDashboardData();
        $this->assertSame(1, $data2['orders']->total_orders);
    }

    public function test_restaurant_lookup_caching_and_invalidation(): void
    {
        $cacheService = app(TenantCacheService::class);

        $cached1 = $cacheService->findRestaurantById($this->restaurant->id);
        $this->assertNotNull($cached1);
        $this->assertSame('Redis Bistro', $cached1->name);

        $cachedBySlug = $cacheService->findRestaurantBySlug('redis-bistro');
        $this->assertNotNull($cachedBySlug);
        $this->assertSame($this->restaurant->id, $cachedBySlug->id);

        // Updating restaurant triggers observer -> invalidates both ID and old/new slug cache
        $this->restaurant->update([
            'name' => 'Redis Bistro Deluxe',
            'slug' => 'redis-bistro-deluxe',
        ]);

        $this->assertFalse(Cache::has('tenant:restaurant:slug:redis-bistro'), 'Old slug must be invalidated');
        $refreshed = $cacheService->findRestaurantById($this->restaurant->id);
        $this->assertSame('Redis Bistro Deluxe', $refreshed->name);
    }

    public function test_restaurant_lookup_does_not_cache_null_on_missing_slug(): void
    {
        $cacheService = app(TenantCacheService::class);
        $missingSlugKey = 'tenant:restaurant:slug:unknown-scanner-path';

        Cache::forget($missingSlugKey);

        $result = $cacheService->findRestaurantBySlug('unknown-scanner-path');
        $this->assertNull($result);
        $this->assertFalse(Cache::has($missingSlugKey), 'Must not cache null to prevent Redis flood from 404 scans');
    }

    public function test_cache_ttls_are_configurable_via_config(): void
    {
        $cacheService = app(TenantCacheService::class);

        config(['tenant_cache.menu_ttl' => 7200]);
        config(['tenant_cache.dashboard_ttl' => 120]);
        config(['tenant_cache.restaurant_ttl' => 300]);

        $this->assertSame(7200, $cacheService->menuTtl());
        $this->assertSame(120, $cacheService->dashboardTtl());
        $this->assertSame(300, $cacheService->restaurantTtl());
    }
}
