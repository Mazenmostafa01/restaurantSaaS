<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShadowCustomerTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant1;

    private Restaurant $restaurant2;

    private User $admin1;

    private User $admin2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin', 'web');

        $this->restaurant1 = Restaurant::create([
            'name' => 'Burger Central',
            'slug' => 'burger-central',
            'email' => 'bc@example.com',
            'is_active' => true,
        ]);

        $this->restaurant2 = Restaurant::create([
            'name' => 'Pizza House',
            'slug' => 'pizza-house',
            'email' => 'ph@example.com',
            'is_active' => true,
        ]);

        $this->admin1 = User::create([
            'name' => 'Cashier 1',
            'email' => 'cashier@burgercentral.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurant1->id,
        ]);
        $this->admin1->assignRole('Admin');

        $this->admin2 = User::create([
            'name' => 'Cashier 2',
            'email' => 'cashier@pizzahouse.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurant2->id,
        ]);
        $this->admin2->assignRole('Admin');
    }

    public function test_staff_can_quick_store_shadow_customer(): void
    {
        $response = $this->actingAs($this->admin1, 'web')
            ->postJson(route('orders.quick-customer'), [
                'name' => 'Caller John',
                'phone_number' => '01011112222',
                'address' => '10 Nile Street, Zamalek',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Customer created successfully.',
            'customer' => [
                'name' => 'Caller John',
                'phone_number' => '01011112222',
                'address' => '10 Nile Street, Zamalek',
            ],
        ]);

        $customer = Customer::withoutGlobalScopes()->where('phone_number', '01011112222')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->restaurant1->id, $customer->restaurant_id);
        $this->assertNull($customer->email);
        $this->assertNull($customer->password);
    }

    public function test_quick_customer_validates_phone_and_name_and_scopes_uniqueness_to_tenant(): void
    {
        // First restaurant creates customer with phone
        Customer::create([
            'restaurant_id' => $this->restaurant1->id,
            'name' => 'Original Customer',
            'phone_number' => '01055556666',
            'address' => 'Address 1',
        ]);

        // Duplicate phone within same restaurant fails
        $response1 = $this->actingAs($this->admin1, 'web')
            ->postJson(route('orders.quick-customer'), [
                'name' => 'Another Customer',
                'phone_number' => '01055556666',
            ]);
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['phone_number']);

        // Same phone in a different restaurant succeeds
        $response2 = $this->actingAs($this->admin2, 'web')
            ->postJson(route('orders.quick-customer'), [
                'name' => 'Pizza Customer with Same Phone',
                'phone_number' => '01055556666',
            ]);
        $response2->assertStatus(201);
    }

    public function test_shadow_customer_can_be_claimed_via_customer_spa_registration(): void
    {
        // 1. Staff creates shadow customer over phone
        $shadowCustomer = Customer::create([
            'restaurant_id' => $this->restaurant1->id,
            'name' => 'Phone Caller Bob',
            'phone_number' => '01099991111',
            'address' => 'Original Phone Address',
            'password' => null,
            'email' => null,
        ]);

        // 2. Staff placed an order for Bob
        $order = Order::create([
            'restaurant_id' => $this->restaurant1->id,
            'customer_id' => $shadowCustomer->id,
            'user_id' => $this->admin1->id,
            'order_number' => 'ORD-9999',
            'price' => 100.00,
            'tax' => 14.00,
            'net' => 114.00,
            'type' => 'delivery',
        ]);

        // 3. Bob goes to the React SPA and registers with the same phone
        $regResponse = $this->postJson("/api/customer/{$this->restaurant1->slug}/register", [
            'name' => 'Bob FullName',
            'email' => 'bob@example.com',
            'phone_number' => '01099991111',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'address' => 'Updated Home Address, Apt 4',
        ]);

        $regResponse->assertStatus(201);
        $regResponse->assertJsonStructure(['message', 'token', 'customer' => ['id', 'name', 'email', 'phone_number']]);

        // Verify Bob's customer record ID remained the same (claimed, not created as duplicate)
        $this->assertEquals($shadowCustomer->id, $regResponse->json('customer.id'));

        $refreshedCustomer = Customer::withoutGlobalScopes()->find($shadowCustomer->id);
        $this->assertEquals('Bob FullName', $refreshedCustomer->name);
        $this->assertEquals('bob@example.com', $refreshedCustomer->email);
        $this->assertEquals('Updated Home Address, Apt 4', $refreshedCustomer->address);
        $this->assertTrue(Hash::check('secret12345', $refreshedCustomer->password));

        // 4. Bob's previous phone order is still attached to his account!
        $this->assertEquals($refreshedCustomer->id, $order->fresh()->customer_id);

        // 5. Bob can now log in with email and password
        $loginResponse = $this->postJson("/api/customer/{$this->restaurant1->slug}/login", [
            'email' => 'bob@example.com',
            'password' => 'secret12345',
        ]);
        $loginResponse->assertStatus(200);

        // 6. Attempting to register again with Bob's phone or email fails with 422
        $duplicateResponse = $this->postJson("/api/customer/{$this->restaurant1->slug}/register", [
            'name' => 'Imposter',
            'email' => 'different@example.com',
            'phone_number' => '01099991111',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'address' => 'Somewhere',
        ]);
        $duplicateResponse->assertStatus(422);
    }
}
