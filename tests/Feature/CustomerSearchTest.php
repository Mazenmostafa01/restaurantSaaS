<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant1;

    private Restaurant $restaurant2;

    private User $admin1;

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
    }

    public function test_customer_search_by_phone_returns_tenant_scoped_results(): void
    {
        Customer::create([
            'restaurant_id' => $this->restaurant1->id,
            'name' => 'Mohamed Salah',
            'email' => 'salah@bc.com',
            'phone_number' => '01012345678',
            'password' => bcrypt('secret'),
            'address' => 'Liverpool',
        ]);

        Customer::create([
            'restaurant_id' => $this->restaurant1->id,
            'name' => 'Mohamed Aly',
            'email' => 'aly@bc.com',
            'phone_number' => '01098765432',
            'password' => bcrypt('secret'),
            'address' => 'Cairo',
        ]);

        // Customer in other restaurant with matching phone prefix
        Customer::create([
            'restaurant_id' => $this->restaurant2->id,
            'name' => 'Mohamed Other',
            'email' => 'other@pizza.com',
            'phone_number' => '01099998888',
            'password' => bcrypt('secret'),
            'address' => 'Alexandria',
        ]);

        $response = $this->actingAs($this->admin1, 'web')
            ->getJson(route('orders.search-customers', ['q' => '010']));

        $response->assertOk();
        $response->assertJsonCount(2, 'customers');

        $phones = collect($response->json('customers'))->pluck('phone_number')->all();
        $this->assertContains('01012345678', $phones);
        $this->assertContains('01098765432', $phones);
        $this->assertNotContains('01099998888', $phones, 'Cross-tenant leak detected!');
    }

    public function test_customer_search_by_phone_number_prefix(): void
    {
        Customer::create([
            'restaurant_id' => $this->restaurant1->id,
            'name' => 'Tarek Hamed',
            'email' => 'tarek@bc.com',
            'phone_number' => '01055554444',
            'password' => bcrypt('secret'),
            'address' => 'Giza',
        ]);

        $response = $this->actingAs($this->admin1, 'web')
            ->getJson(route('orders.search-customers', ['q' => '0105555']));

        $response->assertOk();
        $response->assertJsonCount(1, 'customers');
        $this->assertSame('01055554444', $response->json('customers.0.phone_number'));
        $this->assertSame('Tarek Hamed', $response->json('customers.0.name'));
    }

    public function test_customer_search_filters_strictly_by_phone_number_not_name(): void
    {
        Customer::create([
            'restaurant_id' => $this->restaurant1->id,
            'name' => 'Ahmed Hassan',
            'email' => 'ahmed@bc.com',
            'phone_number' => '01122223333',
            'password' => bcrypt('secret'),
            'address' => 'Cairo',
        ]);

        // Searching by name text should return empty because search is phone-only
        $response = $this->actingAs($this->admin1, 'web')
            ->getJson(route('orders.search-customers', ['q' => 'Ahmed']));

        $response->assertOk();
        $response->assertJsonCount(0, 'customers');

        // Searching by phone prefix returns the customer
        $phoneResponse = $this->actingAs($this->admin1, 'web')
            ->getJson(route('orders.search-customers', ['q' => '0112222']));

        $phoneResponse->assertOk();
        $phoneResponse->assertJsonCount(1, 'customers');
        $this->assertSame('Ahmed Hassan', $phoneResponse->json('customers.0.name'));
    }
}
