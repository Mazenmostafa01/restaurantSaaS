<?php

namespace Tests\Feature;

use App\Enums\ItemCategoryEnum;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\TenantCacheService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantSecurityAndMaliciousRequestsTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurantA;

    private Restaurant $restaurantB;

    private User $adminA;

    private Customer $customerA;

    private Customer $customerA2;

    private Customer $customerB;

    private Item $itemA;

    private Item $itemB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin', 'web');

        $this->restaurantA = Restaurant::create([
            'name' => 'Restaurant Alpha',
            'slug' => 'restaurant-alpha',
            'email' => 'alpha@example.com',
            'is_active' => true,
        ]);

        $this->restaurantB = Restaurant::create([
            'name' => 'Restaurant Beta',
            'slug' => 'restaurant-beta',
            'email' => 'beta@example.com',
            'is_active' => true,
        ]);

        $this->adminA = User::create([
            'name' => 'Admin Alpha',
            'email' => 'admin@alpha.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurantA->id,
        ]);
        $this->adminA->assignRole('Admin');

        $this->customerA = Customer::create([
            'name' => 'Customer A1',
            'email' => 'a1@customer.com',
            'phone_number' => '01011111111',
            'password' => bcrypt('password123'),
            'address' => 'Cairo Street',
            'restaurant_id' => $this->restaurantA->id,
        ]);

        $this->customerA2 = Customer::create([
            'name' => 'Customer A2',
            'email' => 'a2@customer.com',
            'phone_number' => '01022222222',
            'password' => bcrypt('password123'),
            'address' => 'Giza Street',
            'restaurant_id' => $this->restaurantA->id,
        ]);

        $this->customerB = Customer::create([
            'name' => 'Customer B1',
            'email' => 'b1@customer.com',
            'phone_number' => '01033333333',
            'password' => bcrypt('password123'),
            'address' => 'Alexandria Street',
            'restaurant_id' => $this->restaurantB->id,
        ]);

        $this->itemA = Item::create([
            'name' => 'Alpha Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'restaurant_id' => $this->restaurantA->id,
        ]);

        $this->itemB = Item::create([
            'name' => 'Beta Pizza',
            'price' => 200.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'restaurant_id' => $this->restaurantB->id,
        ]);

        app()->forgetInstance(TenantContext::class);
    }

    public function test_customer_menu_served_from_cache_on_second_request_without_touching_db(): void
    {
        $cacheKey = "tenant:{$this->restaurantA->id}:menu";
        Cache::forget($cacheKey);

        $firstResponse = $this->getJson("/api/customer/{$this->restaurantA->slug}/menu");
        $firstResponse->assertOk();
        $this->assertTrue(Cache::has($cacheKey));

        DB::table('items')->where('id', $this->itemA->id)->update(['name' => 'Direct DB Mutation']);

        $secondResponse = $this->getJson("/api/customer/{$this->restaurantA->slug}/menu");
        $secondResponse->assertOk();
        $secondResponse->assertJsonPath('menu.0.items.0.name', 'Alpha Burger');
    }

    public function test_tenant_cache_keys_are_strictly_isolated_and_cannot_be_read_or_poisoned_across_tenants(): void
    {
        $keyA = "tenant:{$this->restaurantA->id}:menu";
        $keyB = "tenant:{$this->restaurantB->id}:menu";
        Cache::forget($keyA);
        Cache::forget($keyB);

        $responseA = $this->getJson("/api/customer/{$this->restaurantA->slug}/menu");
        $responseA->assertOk()->assertJsonPath('menu.0.items.0.name', 'Alpha Burger');

        $responseB = $this->getJson("/api/customer/{$this->restaurantB->slug}/menu");
        $responseB->assertOk()->assertJsonPath('menu.0.items.0.name', 'Beta Pizza');

        $this->assertTrue(Cache::has($keyA));
        $this->assertTrue(Cache::has($keyB));

        app(TenantCacheService::class)->forgetMenu($this->restaurantB->id);

        $this->assertTrue(Cache::has($keyA), 'Tenant A cache must remain intact when Tenant B invalidates');
        $this->assertFalse(Cache::has($keyB), 'Tenant B cache must be cleared');
    }

    public function test_ai_menu_digest_cache_excludes_soft_deleted_items_and_isolates_tenants(): void
    {
        app(TenantContext::class)->set($this->restaurantA);

        $trashedItem = Item::create([
            'name' => 'Trashed Wings',
            'price' => 50.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'restaurant_id' => $this->restaurantA->id,
        ]);
        $trashedItem->delete();

        $cacheService = app(TenantCacheService::class);
        $digest = $cacheService->getAiMenuDigest();

        $names = array_column($digest, 'name');
        $this->assertContains('Alpha Burger', $names);
        $this->assertNotContains('Trashed Wings', $names, 'Soft-deleted items must not leak into AI menu digest');
        $this->assertNotContains('Beta Pizza', $names, 'Cross-tenant items must not leak into AI menu digest');
    }

    public function test_postman_payload_with_conflicting_restaurant_id_is_strictly_ignored_by_admin_and_customer_endpoints(): void
    {
        $this->actingAs($this->adminA)->post(route('items.store'), [
            'name' => 'Conflict Burger',
            'price' => 75.00,
            'category' => 'food',
            'restaurant_id' => 99999,
        ])->assertRedirect(route('items.create'));

        $createdItem = Item::withoutGlobalScopes()->where('name', 'Conflict Burger')->first();
        $this->assertNotNull($createdItem);
        $this->assertSame($this->restaurantA->id, $createdItem->restaurant_id, 'Item must be anchored to authenticated tenant');

        $this->actingAs($this->adminA)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $this->itemA->id => ['selected' => 'on', 'quantity' => 1],
            ],
            'restaurant_id' => 99999,
        ])->assertRedirect(route('orders.create'));

        $createdOrder = Order::withoutGlobalScopes()->where('user_id', $this->adminA->id)->latest('id')->first();
        $this->assertSame($this->restaurantA->id, $createdOrder->restaurant_id, 'Order must be anchored to authenticated tenant');

        $token = $this->customerA->createToken($this->restaurantA->slug)->plainTextToken;
        $this->postJson("/api/customer/{$this->restaurantA->slug}/orders", [
            'type' => 'take_away',
            'items' => [
                ['id' => $this->itemA->id, 'quantity' => 1],
            ],
            'restaurant_id' => 99999,
        ], ['Authorization' => 'Bearer '.$token])->assertStatus(201);

        $customerOrder = Order::withoutGlobalScopes()->where('customer_id', $this->customerA->id)->latest('id')->first();
        $this->assertSame($this->restaurantA->id, $customerOrder->restaurant_id, 'Customer order must be anchored to route tenant');

        $this->postJson("/api/customer/{$this->restaurantA->slug}/register", [
            'name' => 'New User',
            'email' => 'newuser@alpha.com',
            'phone_number' => '01098765432',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => 'Cairo',
            'restaurant_id' => 99999,
        ])->assertStatus(201);

        $newCustomer = Customer::withoutGlobalScopes()->where('email', 'newuser@alpha.com')->first();
        $this->assertSame($this->restaurantA->id, $newCustomer->restaurant_id, 'Registered customer must be scoped to route tenant');
    }

    public function test_order_creation_rejects_mixed_active_and_soft_deleted_items_for_both_admin_and_customer(): void
    {
        $trashedItem = Item::create([
            'name' => 'Deleted Taco',
            'price' => 45.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'restaurant_id' => $this->restaurantA->id,
        ]);
        $trashedItem->delete();

        $response = $this->actingAs($this->adminA)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $this->itemA->id => ['selected' => 'on', 'quantity' => 2],
                $trashedItem->id => ['selected' => 'on', 'quantity' => 1],
            ],
            'note' => 'malicious_mixed_order',
        ]);
        $response->assertSessionHasErrors(['items' => 'One or more selected items are invalid for this restaurant.']);
        $this->assertDatabaseMissing('orders', ['note' => 'malicious_mixed_order']);

        $token = $this->customerA->createToken($this->restaurantA->slug)->plainTextToken;
        $this->postJson("/api/customer/{$this->restaurantA->slug}/orders", [
            'type' => 'take_away',
            'items' => [
                ['id' => $this->itemA->id, 'quantity' => 2],
                ['id' => $trashedItem->id, 'quantity' => 1],
            ],
            'note' => 'malicious_mixed_customer_order',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.1.id']);

        $this->assertDatabaseMissing('orders', ['note' => 'malicious_mixed_customer_order']);
    }

    public function test_cross_tenant_item_ids_in_order_creation_are_rejected_with_validation_errors(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $this->itemA->id => ['selected' => 'on', 'quantity' => 1],
                $this->itemB->id => ['selected' => 'on', 'quantity' => 1],
            ],
        ]);
        $response->assertSessionHasErrors(['items' => 'One or more selected items are invalid for this restaurant.']);

        $token = $this->customerA->createToken($this->restaurantA->slug)->plainTextToken;
        $this->postJson("/api/customer/{$this->restaurantA->slug}/orders", [
            'type' => 'take_away',
            'items' => [
                ['id' => $this->itemB->id, 'quantity' => 1],
            ],
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.id']);
    }

    public function test_cross_tenant_item_restore_route_returns_404_not_found(): void
    {
        $trashedItemB = Item::withoutGlobalScopes()->create([
            'name' => 'Trashed Beta Item',
            'price' => 90.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'restaurant_id' => $this->restaurantB->id,
        ]);
        $trashedItemB->delete();

        $this->actingAs($this->adminA)->post(route('items.restore', $trashedItemB->id))->assertStatus(404);

        $fresh = Item::withoutGlobalScopes()->find($trashedItemB->id);
        $this->assertNotNull($fresh);
        $this->assertNotNull($fresh->deleted_at, 'Other tenant item must remain soft-deleted');
    }

    public function test_customer_route_model_binding_returns_404_instead_of_403_to_prevent_existence_oracle(): void
    {
        $orderB = Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-B-1',
            'price' => 200.00,
            'tax' => 28.00,
            'net' => 228.00,
            'type' => 'take_away',
            'customer_id' => $this->customerB->id,
            'restaurant_id' => $this->restaurantB->id,
        ]);

        $orderA2 = Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-A2-1',
            'price' => 100.00,
            'tax' => 14.00,
            'net' => 114.00,
            'type' => 'take_away',
            'customer_id' => $this->customerA2->id,
            'restaurant_id' => $this->restaurantA->id,
        ]);

        $tokenA = $this->customerA->createToken($this->restaurantA->slug)->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$tokenA];

        $this->getJson("/api/customer/{$this->restaurantA->slug}/orders/{$orderB->id}", $headers)
            ->assertStatus(404);

        $this->getJson("/api/customer/{$this->restaurantA->slug}/orders/{$orderA2->id}", $headers)
            ->assertStatus(404)
            ->assertJson(['message' => 'Order not found.']);
    }

    public function test_customer_bearer_token_rejected_with_401_when_accessing_different_restaurant_slug(): void
    {
        $tokenA = $this->customerA->createToken($this->restaurantA->slug)->plainTextToken;

        $this->getJson("/api/customer/{$this->restaurantB->slug}/orders", [
            'Authorization' => 'Bearer '.$tokenA,
        ])->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_cross_tenant_tampering_cannot_flush_other_tenant_cache(): void
    {
        $keyB = "tenant:{$this->restaurantB->id}:menu";
        Cache::put($keyB, ['safe_menu_data'], 3600);

        $this->actingAs($this->adminA)->put(route('items.update', $this->itemB->id), [
            'name' => 'Tampered Name',
            'price' => 1.00,
            'category' => 'food',
        ])->assertStatus(404);

        $this->actingAs($this->adminA)->delete(route('items.delete', $this->itemB->id))->assertStatus(404);

        $this->assertTrue(Cache::has($keyB), "Tenant B's cache must not be invalidated by Tenant A unauthorized actions");
    }

    public function test_query_filter_fuzzing_with_array_input_does_not_crash_endpoints(): void
    {
        // Tests the fix against ?q[]= array fuzzing causing 500 error
        $this->actingAs($this->adminA)
            ->get(route('orders.index', ['q' => ['malicious' => 'array']]))
            ->assertOk();

        $this->actingAs($this->adminA)
            ->get(route('items.index', ['q' => ['malicious' => 'array']]))
            ->assertOk();

        $this->actingAs($this->adminA)
            ->getJson(route('orders.search-customers', ['q' => ['malicious' => 'array']]))
            ->assertOk();
    }
}
