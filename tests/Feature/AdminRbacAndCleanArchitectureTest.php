<?php

namespace Tests\Feature;

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

        // Employee attempts to access admin-only item edit
        $response = $this->actingAs($this->employeeUser)->get(route('items.edit', $item));
        $response->assertStatus(403);

        // Employee attempts to access admin-only order edit
        $response = $this->actingAs($this->employeeUser)->get(route('orders.edit', $order));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $item = Item::create([
            'name' => 'Calzone',
            'price' => 12.00,
            'category' => 'food',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('items.edit', $item));
        $response->assertStatus(200);
    }

    public function test_order_creation_stores_order_with_batch_items(): void
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

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $item1->id => ['selected' => 'on', 'quantity' => 2],
                $item2->id => ['selected' => 'on', 'quantity' => 1],
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
}
