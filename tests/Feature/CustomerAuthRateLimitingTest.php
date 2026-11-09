<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost', '127.0.0.1']]);

        $this->restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'email' => 'test@restaurant.com',
            'phone' => '1234567890',
            'address' => '123 Main St',
            'is_active' => true,
        ]);
    }

    public function test_login_rate_limiting_triggers_after_max_attempts(): void
    {
        $email = 'customer@test.com';
        $headers = [
            'Referer' => 'http://localhost',
            'Accept' => 'application/json',
        ];

        // Attempt login 5 times with bad password
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
                'email' => $email,
                'password' => 'wrongpassword',
            ], $headers);

            $response->assertStatus(401);
        }

        // The 6th attempt should return a 429 Too Many Requests even with uppercase email and missing password
        $response = $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => strtoupper($email),
        ], $headers);

        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
        $this->assertStringContainsString('Too many login attempts', $response->json('message'));
    }

    public function test_successful_login_clears_rate_limiting(): void
    {
        $email = 'customer@test.com';
        $password = 'password123';
        $headers = [
            'Referer' => 'http://localhost',
            'Accept' => 'application/json',
        ];

        // Create the customer
        Customer::create([
            'name' => 'Customer Name',
            'email' => $email,
            'password' => $password, // auto hashed by model cast
            'phone_number' => '0987654321',
            'address' => '123 Customer St',
            'restaurant_id' => $this->restaurant->id,
        ]);

        // Attempt login with incorrect password 3 times
        for ($i = 0; $i < 3; $i++) {
            $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
                'email' => $email,
                'password' => 'wrongpassword',
            ], $headers)->assertStatus(401);
        }

        // Successful login
        $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => $email,
            'password' => $password,
        ], $headers)->assertStatus(200);

        // Verify the rate limiter is cleared and we can try again
        $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => $email,
            'password' => 'wrongpassword',
        ], $headers)->assertStatus(401);
    }

    public function test_register_rate_limiting_triggers_after_max_failed_attempts(): void
    {
        $email = 'existing@test.com';
        $headers = [
            'Referer' => 'http://localhost',
            'Accept' => 'application/json',
        ];

        // Create existing customer to cause registration failures
        Customer::create([
            'name' => 'Existing Customer',
            'email' => $email,
            'password' => 'password123',
            'phone_number' => '0987654321',
            'address' => '123 Customer St',
            'restaurant_id' => $this->restaurant->id,
        ]);

        // Try registering the same email 5 times
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/register", [
                'name' => 'New Customer',
                'email' => $email,
                'phone_number' => '0987654321',
                'address' => '123 Customer St',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ], $headers);

            $response->assertStatus(422);
        }

        // The 6th attempt should return 429
        $response = $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/register", [
            'name' => 'New Customer',
            'email' => $email,
            'phone_number' => '0987654321',
            'address' => '123 Customer St',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $headers);

        $response->assertStatus(429);
        $this->assertStringContainsString('Too many registration attempts', $response->json('message'));
    }

    public function test_staff_web_login_rate_limiting_triggers_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email_or_username' => 'admin@restaurant.com',
                'password' => 'wrongpassword',
            ])->assertRedirect('/login')
                ->assertSessionHasErrors(['error' => 'wrong username or password']);
        }

        $this->from('/login')->post('/login', [
            'email_or_username' => 'admin@restaurant.com',
            'password' => 'wrongpassword',
        ])->assertStatus(429);
    }

    public function test_login_rate_limiting_is_isolated_per_tenant(): void
    {
        $email = 'shared_diner@test.com';
        $headers = ['Referer' => 'http://localhost', 'Accept' => 'application/json'];

        $restaurantB = Restaurant::create([
            'name' => 'Second Restaurant',
            'slug' => 'second-restaurant',
            'email' => 'second@restaurant.com',
            'phone' => '0987654321',
            'address' => '456 Second St',
            'is_active' => true,
        ]);

        Customer::create([
            'restaurant_id' => $restaurantB->id,
            'name' => 'Shared Diner',
            'email' => $email,
            'phone_number' => '01055551111',
            'password' => 'correctPassword123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
                'email' => $email,
                'password' => 'wrongPassword',
            ], $headers)->assertStatus(401);
        }

        $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => $email,
            'password' => 'wrongPassword',
        ], $headers)->assertStatus(429);

        $tenantBResponse = $this->withSession([])->postJson("/api/customer/{$restaurantB->slug}/login", [
            'email' => $email,
            'password' => 'correctPassword123',
        ], $headers);

        $tenantBResponse->assertStatus(200);
        $this->assertArrayHasKey('token', $tenantBResponse->json());
    }

    public function test_registration_rejects_nested_array_payload_for_string_fields(): void
    {
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/register", [
            'name' => ['malicious' => 'nested array'],
            'email' => ['injected@email.com'],
            'phone_number' => ['01012345678'],
            'password' => ['password123'],
            'password_confirmation' => ['password123'],
            'address' => ['street' => 'nested address'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'phone_number', 'password', 'address']);
    }

    public function test_customer_login_rejects_nested_array_payload(): void
    {
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => ['admin@restaurant.com'],
            'password' => ['password123'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
