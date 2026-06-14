<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
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

        // The 6th attempt should return a 429 Too Many Requests
        $response = $this->withSession([])->postJson("/api/customer/{$this->restaurant->slug}/login", [
            'email' => $email,
            'password' => 'wrongpassword',
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
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $headers);

        $response->assertStatus(429);
        $this->assertStringContainsString('Too many registration attempts', $response->json('message'));
    }
}
