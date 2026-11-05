<?php

namespace Tests\Feature;

use App\Ai\Agents\OrderParserAgent;
use App\Enums\ItemCategoryEnum;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerOrderParseTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('ai-order-parse');

        $this->restaurant = Restaurant::create([
            'name' => 'Burger House',
            'slug' => 'burger-house',
            'email' => 'info@burgerhouse.com',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Mohamed Ali',
            'email' => 'mali@example.com',
            'phone_number' => '01012345678',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '2 burgers',
        ]);

        $response->assertStatus(401);
    }

    public function test_cross_tenant_customer_cannot_parse_orders(): void
    {
        $otherRestaurant = Restaurant::create([
            'name' => 'Pizza Corner',
            'slug' => 'pizza-corner',
            'email' => 'info@pizzacorner.com',
            'is_active' => true,
        ]);

        $otherCustomer = Customer::create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Other Diner',
            'email' => 'other@example.com',
            'phone_number' => '01099998888',
            'password' => bcrypt('password123'),
        ]);

        Sanctum::actingAs($otherCustomer, ['*']);

        // Attempts to access Burger House endpoint with a Pizza Corner customer token
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '2 burgers',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_404_if_restaurant_inactive_or_not_found(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        // Missing restaurant
        $response = $this->postJson('/api/customer/non-existent-restaurant/orders/parse', [
            'text' => '2 burgers',
        ]);
        $response->assertStatus(404);

        // Inactive restaurant
        $this->restaurant->update(['is_active' => false]);
        Cache::flush();

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '2 burgers',
        ]);
        $response->assertStatus(404);
    }

    public function test_validates_that_either_text_or_audio_is_required(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['text', 'audio']);
    }

    public function test_validates_audio_mimetype_and_size(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        // Invalid MIME type (text/plain)
        $invalidFile = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'audio' => $invalidFile,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['audio']);

        // Oversized file (> 3072 KB)
        $oversizedFile = UploadedFile::fake()->create('large.webm', 4000, 'audio/webm');
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'audio' => $oversizedFile,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['audio']);
    }

    public function test_parses_natural_language_order_successfully(): void
    {
        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Classic Burger',
            'price' => 120.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $cola = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Cola Can',
            'price' => 30.00,
            'category' => ItemCategoryEnum::BEVERAGE->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Classic Burger',
                        'quantity' => 2,
                        'note' => 'no onions',
                        'person' => null,
                        'confidence' => 0.98,
                    ],
                    [
                        'id' => $cola->id,
                        'name' => 'Cola Can',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.99,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => ['ring bell'],
                'splits' => [],
                'language_detected' => 'ar_eg',
            ],
        ]);

        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '2 burger mn gher basal w 1 cola, w ren el garas',
            'preferred_language' => 'ar_eg',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'items' => [
                '*' => ['id', 'name', 'quantity', 'price', 'subtotal', 'note'],
            ],
            'unmatched',
            'note',
            'subtotal',
            'tax',
            'net',
        ]);

        // Authoritative math: 120 * 2 = 240, 30 * 1 = 30 -> Subtotal: 270.00, 14% tax: 37.80, Net: 307.80
        $response->assertJson([
            'subtotal' => '270.00',
            'tax' => '37.80',
            'net' => '307.80',
            'note' => 'ring bell | Classic Burger: no onions',
            'items' => [
                [
                    'id' => $burger->id,
                    'name' => 'Classic Burger',
                    'quantity' => 2,
                    'price' => '120.00',
                    'subtotal' => '240.00',
                    'note' => 'no onions',
                ],
                [
                    'id' => $cola->id,
                    'name' => 'Cola Can',
                    'quantity' => 1,
                    'price' => '30.00',
                    'subtotal' => '30.00',
                    'note' => null,
                ],
            ],
        ]);
    }

    public function test_parses_uploaded_audio_voice_order_successfully(): void
    {
        $pizza = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Pepperoni Pizza',
            'price' => 160.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $pizza->id,
                        'name' => 'Pepperoni Pizza',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.95,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'en',
            ],
        ]);

        Sanctum::actingAs($this->customer, ['*']);

        $audioFile = UploadedFile::fake()->create('voice.webm', 250, 'audio/webm');

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'audio' => $audioFile,
        ]);

        $response->assertOk();
        $response->assertJson([
            'subtotal' => '160.00',
            'tax' => '22.40',
            'net' => '182.40',
            'items' => [
                [
                    'id' => $pizza->id,
                    'name' => 'Pepperoni Pizza',
                    'quantity' => 1,
                    'price' => '160.00',
                    'subtotal' => '160.00',
                ],
            ],
        ]);

        OrderParserAgent::assertPrompted(function ($prompt) {
            return $prompt->attachments->isNotEmpty();
        });
    }

    public function test_rate_limiter_throttles_after_10_requests_per_minute(): void
    {
        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake(function () use ($burger) {
            return [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Burger',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.99,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'en',
            ];
        });

        Sanctum::actingAs($this->customer, ['*']);

        // First 10 requests succeed
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
                'text' => '1 burger',
            ]);
            $response->assertOk();
        }

        // 11th request is throttled with 429 Too Many Requests
        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '1 burger',
        ]);
        $response->assertStatus(429);
    }

    public function test_empty_menu_returns_graceful_empty_parsed_order(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        OrderParserAgent::fake();

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '2 burgers',
        ]);

        $response->assertOk();
        $response->assertJson([
            'items' => [],
            'unmatched' => [],
            'subtotal' => '0.00',
            'tax' => '0.00',
            'net' => '0.00',
            'note' => 'Menu is currently empty. Please try again later.',
        ]);

        OrderParserAgent::assertNeverPrompted();
    }

    public function test_handles_provider_connection_exception_with_504_json(): void
    {
        Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake(function () {
            throw ProviderConnectionException::forProvider('gemini', 0, new \Exception('cURL error 28: Operation timed out'));
        });

        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '1 burger',
        ]);

        $response->assertStatus(504);
        $response->assertJsonStructure(['message']);
    }

    public function test_handles_provider_overloaded_exception_with_503_json(): void
    {
        Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake(function () {
            throw ProviderOverloadedException::forProvider('gemini', 503, new \Exception('High demand'));
        });

        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson("/api/customer/{$this->restaurant->slug}/orders/parse", [
            'text' => '1 burger',
        ]);

        $response->assertStatus(503);
        $response->assertJsonStructure(['message']);
    }
}
