<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRbacAndCleanArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private User $adminUser;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);

        $this->restaurant = Restaurant::create([
            'name' => 'Pizza Palace',
            'slug' => 'pizza-palace',
            'email' => 'contact@pizzapalace.com',
            'phone' => '1234567890',
            'address' => '101 Italian Way',
            'is_active' => true,
        ]);

        app(TenantContext::class)->set($this->restaurant);

        $this->adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin@pizzapalace.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurant->id,
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->employeeUser = User::create([
            'name' => 'Employee Staff',
            'email' => 'staff@pizzapalace.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurant->id,
        ]);
        $this->employeeUser->assignRole($employeeRole);
    }

    public function test_restaurant_model_casts_method_works(): void
    {
        $this->assertIsBool($this->restaurant->is_active);
        $this->assertTrue($this->restaurant->is_active);
    }

    public function test_item_and_order_casts_work(): void
    {
        $item = Item::create([
            'name' => 'Margherita',
            'price' => 15.5,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $this->assertEquals('15.50', $item->price);

        $order = Order::create([
            'order_number' => 'ORD-12345',
            'price' => 15.5,
            'tax' => 2.17,
            'net' => 17.67,
            'type' => 'take_away',
            'restaurant_id' => $this->restaurant->id,
            'user_id' => $this->adminUser->id,
        ]);

        $this->assertEquals('15.50', $order->price);
        $this->assertEquals('2.17', $order->tax);
        $this->assertEquals('17.67', $order->net);
        $this->assertEquals($this->adminUser->id, $order->user->id);
        $this->assertTrue($this->adminUser->orders->contains($order));
    }

    public function test_employee_is_forbidden_from_admin_item_and_order_routes(): void
    {
        $item = Item::create([
            'name' => 'Pepperoni',
            'price' => 18.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-999',
            'price' => 18.00,
            'tax' => 2.52,
            'net' => 20.52,
            'type' => 'take_away',
            'restaurant_id' => $this->restaurant->id,
            'user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->employeeUser)->get(route('items.edit', $item))->assertStatus(403);
        $this->actingAs($this->employeeUser)->put(route('items.update', $item), [])->assertStatus(403);
        $this->actingAs($this->employeeUser)->delete(route('items.delete', $item))->assertStatus(403);
        $this->actingAs($this->employeeUser)->post(route('items.restore', $item->id))->assertStatus(403);

        $this->actingAs($this->employeeUser)->get(route('orders.edit', $order))->assertStatus(403);
        $this->actingAs($this->employeeUser)->put(route('orders.update', $order), [])->assertStatus(403);
        $this->actingAs($this->employeeUser)->delete(route('orders.delete', $order))->assertStatus(403);

        $this->actingAs($this->employeeUser)->get(route('dashboard'))->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $item = Item::create([
            'name' => 'Calzone',
            'price' => 12.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $this->actingAs($this->adminUser)->get(route('items.edit', $item))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('orders.create'))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('orders.index'))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('dashboard'))->assertStatus(200);
    }

    public function test_admin_can_access_orders_edit_and_update_order(): void
    {
        $customer = Customer::create([
            'name' => 'Original Customer',
            'phone_number' => '01012345678',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $item1 = Item::create([
            'name' => 'Pizza Margarita',
            'price' => 15.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $item2 = Item::create([
            'name' => 'Cola',
            'price' => 5.00,
            'category' => 'beverage',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-101',
            'price' => 30.00,
            'tax' => 4.20,
            'net' => 34.20,
            'type' => 'delivery',
            'note' => 'Ring bell',
            'user_id' => $this->adminUser->id,
            'customer_id' => $customer->id,
            'restaurant_id' => $this->restaurant->id,
        ]);
        $order->items()->attach([$item1->id => ['quantity' => 2]]);

        // Admin loads edit view
        $response = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));
        $response->assertStatus(200);
        $response->assertSee('ORD-101');
        $response->assertSee('Original Customer');
        $response->assertSee('Pizza Margarita');

        // Admin updates the order
        $updateResponse = $this->actingAs($this->adminUser)->put(route('orders.update', $order), [
            'type' => 'take_away',
            'customer_id' => $customer->id,
            'items' => [
                $item1->id => ['selected' => 'on', 'quantity' => 1],
                $item2->id => ['selected' => 'on', 'quantity' => 3],
            ],
            'note' => 'Updated note',
        ]);

        $updateResponse->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'type' => 'take_away',
            'note' => 'Updated note',
            'price' => 30.00,
            'tax' => 4.20,
            'net' => 34.20,
        ]);

        $this->assertCount(2, $order->fresh()->items);
    }

    public function test_delivery_order_requires_customer_on_create_and_update_while_takeaway_is_optional(): void
    {
        $item = Item::create([
            'name' => 'Pizza Delivery',
            'price' => 20.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $customer = Customer::create([
            'name' => 'Delivery Client',
            'phone_number' => '01099998888',
            'restaurant_id' => $this->restaurant->id,
        ]);

        // 1. Delivery order without customer fails validation
        $failResponse = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'type' => 'delivery',
            'customer_id' => null,
            'items' => [
                $item->id => ['selected' => 'on', 'quantity' => 1],
            ],
        ]);
        $failResponse->assertSessionHasErrors(['customer_id' => 'A customer is required for delivery orders.']);

        // 2. Take-away order without customer succeeds
        $takeawayResponse = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'type' => 'take_away',
            'customer_id' => null,
            'items' => [
                $item->id => ['selected' => 'on', 'quantity' => 1],
            ],
        ]);
        $takeawayResponse->assertRedirect(route('orders.create'));

        $createdOrder = Order::where('type', 'take_away')->latest('id')->first();
        $this->assertNotNull($createdOrder);
        $this->assertNull($createdOrder->customer_id);

        // 3. Updating an order to delivery without customer fails validation
        $updateFailResponse = $this->actingAs($this->adminUser)->put(route('orders.update', $createdOrder), [
            'type' => 'delivery',
            'customer_id' => null,
            'items' => [
                $item->id => ['selected' => 'on', 'quantity' => 1],
            ],
        ]);
        $updateFailResponse->assertSessionHasErrors(['customer_id' => 'A customer is required for delivery orders.']);

        // 4. Updating an order to delivery with valid customer succeeds
        $updateSuccessResponse = $this->actingAs($this->adminUser)->put(route('orders.update', $createdOrder), [
            'type' => 'delivery',
            'customer_id' => $customer->id,
            'items' => [
                $item->id => ['selected' => 'on', 'quantity' => 1],
            ],
        ]);
        $updateSuccessResponse->assertRedirect(route('orders.index'));
        $this->assertEquals($customer->id, $createdOrder->fresh()->customer_id);
    }

    public function test_order_creation_stores_order_with_batch_items_and_ignores_empty_rows(): void
    {
        $item1 = Item::create([
            'name' => 'Pizza A',
            'price' => 10.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $item2 = Item::create([
            'name' => 'Drink B',
            'price' => 5.00,
            'category' => 'beverage',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $item3 = Item::create([
            'name' => 'Unchecked Item',
            'price' => 50.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $item1->id => ['selected' => 'on', 'quantity' => 2],
                $item2->id => ['selected' => 'on', 'quantity' => 1],
                $item3->id => [],
            ],
            'note' => 'Extra crispy',
        ]);

        $response->assertRedirect(route('orders.create'));

        $this->assertDatabaseHas('orders', [
            'restaurant_id' => $this->restaurant->id,
            'type' => 'take_away',
            'price' => 25.00,
            'tax' => 3.50,
            'net' => 28.50,
        ]);

        $createdOrder = Order::latest('id')->first();
        $this->assertCount(2, $createdOrder->items);
    }

    public function test_dashboard_analytics_service_aggregates_tenant_sales_and_isolates_other_tenants(): void
    {
        $item = Item::create([
            'name' => 'Truffle Pizza',
            'price' => 20.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $item->id => ['selected' => 'on', 'quantity' => 3],
            ],
        ]);

        // Create a second tenant with its own item and order to prove tenant isolation on SQL join
        $otherRestaurant = Restaurant::create([
            'name' => 'Burger Barn',
            'slug' => 'burger-barn',
            'email' => 'info@burgerbarn.com',
            'phone' => '9998887777',
            'address' => '202 Burger Ave',
            'is_active' => true,
        ]);

        $otherOrder = Order::withoutGlobalScopes()->create([
            'order_number' => 'OTHER-1',
            'price' => 500.00,
            'tax' => 70.00,
            'net' => 570.00,
            'type' => 'take_away',
            'restaurant_id' => $otherRestaurant->id,
        ]);
        $otherOrder->items()->attach([$item->id => ['quantity' => 10]]);

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('topItems', function ($topItems): bool {
            return $topItems->count() === 1
                && $topItems->first()->name === 'Truffle Pizza'
                && (float) $topItems->first()->total_sales === 60.0;
        });
    }

    public function test_customer_order_rejects_soft_deleted_items_and_profile_update_preserves_non_null_address(): void
    {
        $activeItem = Item::create([
            'name' => 'Active Pasta',
            'price' => 30.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $trashedItem = Item::create([
            'name' => 'Trashed Pasta',
            'price' => 25.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);
        $trashedItem->delete();

        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'phone_number' => '01012345678',
            'address' => '42 Nile St',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $token = $customer->createToken($this->restaurant->slug)->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        // Ordering soft-deleted item fails with 422
        $this->postJson("/api/customer/{$this->restaurant->slug}/orders", [
            'type' => 'take_away',
            'items' => [
                ['id' => $trashedItem->id, 'quantity' => 1],
            ],
        ], $headers)->assertStatus(422);

        // Ordering active item succeeds with 201
        $this->postJson("/api/customer/{$this->restaurant->slug}/orders", [
            'type' => 'take_away',
            'items' => [
                ['id' => $activeItem->id, 'quantity' => 2],
            ],
        ], $headers)->assertStatus(201)->assertJsonPath('order.net', '68.40');

        // Updating profile with empty address preserves existing address instead of throwing 500
        $this->putJson("/api/customer/{$this->restaurant->slug}/profile", [
            'name' => 'Jane Updated',
            'address' => '',
        ], $headers)->assertStatus(200)
            ->assertJsonPath('customer.name', 'Jane Updated')
            ->assertJsonPath('customer.address', '42 Nile St');
    }

    public function test_route_model_binding_blocks_cross_tenant_item_and_order_access(): void
    {
        $otherRestaurant = Restaurant::create([
            'name' => 'Sushi Spot',
            'slug' => 'sushi-spot',
            'email' => 'hello@sushispot.com',
            'phone' => '5551234567',
            'address' => '99 Tokyo Dr',
            'is_active' => true,
        ]);

        $otherItem = Item::withoutGlobalScopes()->create([
            'name' => 'Dragon Roll',
            'price' => 22.00,
            'category' => 'food',
            'restaurant_id' => $otherRestaurant->id,
        ]);

        $otherOrder = Order::withoutGlobalScopes()->create([
            'order_number' => 'SUSHI-1',
            'price' => 22.00,
            'tax' => 3.08,
            'net' => 25.08,
            'type' => 'take_away',
            'restaurant_id' => $otherRestaurant->id,
        ]);

        // Clear any pre-set TenantContext from setUp so the HTTP middleware pipeline resolves it
        app()->forgetInstance(TenantContext::class);

        $this->actingAs($this->adminUser)->get(route('items.show', $otherItem->id))->assertStatus(404);
        $this->actingAs($this->adminUser)->get(route('items.edit', $otherItem->id))->assertStatus(404);
        $this->actingAs($this->adminUser)->put(route('items.update', $otherItem->id), [
            'name' => 'Hijacked Roll',
            'price' => 1.00,
            'category' => 'food',
        ])->assertStatus(404);
        $this->actingAs($this->adminUser)->delete(route('items.delete', $otherItem->id))->assertStatus(404);

        $this->actingAs($this->adminUser)->get(route('orders.edit', $otherOrder->id))->assertStatus(404);
        $this->actingAs($this->adminUser)->put(route('orders.update', $otherOrder->id), [
            'type' => 'delivery',
        ])->assertStatus(404);
        $this->actingAs($this->adminUser)->delete(route('orders.delete', $otherOrder->id))->assertStatus(404);

        // Verify the other tenant's records were not modified or soft-deleted
        $freshItem = Item::withoutGlobalScopes()->find($otherItem->id);
        $this->assertNotNull($freshItem);
        $this->assertNull($freshItem->deleted_at);
        $this->assertSame('Dragon Roll', $freshItem->name);

        $freshOrder = Order::withoutGlobalScopes()->find($otherOrder->id);
        $this->assertNotNull($freshOrder);
        $this->assertNull($freshOrder->deleted_at);
    }

    public function test_same_item_name_is_allowed_across_different_restaurants(): void
    {
        $otherRestaurant = Restaurant::create([
            'name' => 'Taco Town',
            'slug' => 'taco-town',
            'email' => 'hello@tacotown.com',
            'is_active' => true,
        ]);

        Item::create([
            'name' => 'Caesar Salad',
            'price' => 95.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $secondItem = Item::withoutGlobalScopes()->create([
            'name' => 'Caesar Salad',
            'price' => 100.00,
            'category' => 'food',
            'restaurant_id' => $otherRestaurant->id,
        ]);

        $this->assertNotNull($secondItem->id);
        $this->assertSame($otherRestaurant->id, $secondItem->restaurant_id);
    }

    public function test_admin_web_session_cannot_authenticate_as_customer_on_customer_api(): void
    {
        config(['sanctum.stateful' => ['localhost', '127.0.0.1']]);

        $customerWithSameId = Customer::create([
            'name' => 'Real Customer',
            'email' => 'real@customer.com',
            'phone_number' => '01099887766',
            'password' => 'password123',
            'address' => '10 Real St',
            'restaurant_id' => $this->restaurant->id,
        ]);

        Order::create([
            'order_number' => 'CUST-ORD-1',
            'price' => 50.00,
            'tax' => 7.00,
            'net' => 57.00,
            'type' => 'take_away',
            'customer_id' => $customerWithSameId->id,
            'restaurant_id' => $this->restaurant->id,
        ]);

        app()->forgetInstance(TenantContext::class);

        $this->actingAs($this->adminUser, 'web')
            ->getJson("/api/customer/{$this->restaurant->slug}/orders", ['Referer' => 'http://localhost'])
            ->assertStatus(401);

        $this->actingAs($this->adminUser, 'web')
            ->postJson("/api/customer/{$this->restaurant->slug}/logout", [], ['Referer' => 'http://localhost'])
            ->assertStatus(401);
    }

    public function test_user_without_restaurant_id_is_forbidden_from_tenant_routes(): void
    {
        app()->forgetInstance(TenantContext::class);

        $orphanUser = User::withoutGlobalScopes()->create([
            'name' => 'Orphan User',
            'email' => 'orphan@example.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => null,
        ]);
        $orphanUser->assignRole('Admin');

        $this->actingAs($orphanUser, 'web')
            ->get(route('items.index'))
            ->assertStatus(403);
    }

    public function test_soft_deleted_items_remain_visible_on_existing_orders_and_edit_preserves_them(): void
    {
        $item1 = Item::create([
            'name' => 'Seasonal Soup',
            'price' => 40.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $item2 = Item::create([
            'name' => 'Garlic Bread',
            'price' => 20.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $customer = Customer::create([
            'name' => 'Soup Lover',
            'email' => 'soup@example.com',
            'password' => 'password123',
            'phone_number' => '01011223344',
            'address' => '12 Soup St',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SOUP',
            'price' => 60.00,
            'tax' => 8.40,
            'net' => 68.40,
            'type' => 'take_away',
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'restaurant_id' => $this->restaurant->id,
        ]);
        $order->items()->attach([
            $item1->id => ['quantity' => 1],
            $item2->id => ['quantity' => 1],
        ]);

        // Soft-delete item1 after order was placed
        $item1->delete();

        $token = $customer->createToken($this->restaurant->slug)->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        // Customer order history & detail still show the soft-deleted item
        $this->getJson("/api/customer/{$this->restaurant->slug}/orders", $headers)
            ->assertOk()
            ->assertJsonPath('orders.data.0.items_count', 2);

        $this->getJson("/api/customer/{$this->restaurant->slug}/orders/{$order->id}", $headers)
            ->assertOk()
            ->assertJsonCount(2, 'order.items');

        // Admin editing the order can keep the soft-deleted item without losing it
        $this->actingAs($this->adminUser)->put(route('orders.update', $order), [
            'type' => 'take_away',
            'items' => [
                $item1->id => ['selected' => 'on', 'quantity' => 1],
                $item2->id => ['selected' => 'on', 'quantity' => 2],
            ],
            'note' => 'Added extra bread',
        ])->assertRedirect(route('orders.index'));

        $this->assertCount(2, $order->fresh()->items);
        $this->assertEquals('80.00', $order->fresh()->price);
    }

    public function test_array_inputs_in_customer_auth_requests_return_422_instead_of_500(): void
    {
        $this->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => ['not-a-string'],
            'password' => ['not-a-string'],
        ])->assertStatus(422);

        $this->postJson("/api/customer/{$this->restaurant->slug}/register", [
            'name' => 'Test',
            'email' => ['array@email.com'],
            'phone_number' => '01012345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => str_repeat('a', 101),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'address']);
    }
}
