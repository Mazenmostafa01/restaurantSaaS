<?php

namespace Tests\Unit;

use App\Ai\Agents\OrderParserAgent;
use App\DTOs\OrderParseData;
use App\Enums\ItemCategoryEnum;
use App\Models\Item;
use App\Models\Restaurant;
use App\Services\NaturalLanguageOrderParserService;
use App\Services\TenantCacheService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NaturalLanguageOrderParserServiceTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private TenantContext $tenantContext;

    private TenantCacheService $cacheService;

    private NaturalLanguageOrderParserService $parserService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::create([
            'name' => 'Cairo Grill',
            'slug' => 'cairo-grill',
            'email' => 'cairo@grill.com',
            'is_active' => true,
        ]);

        $this->tenantContext = app(TenantContext::class);
        $this->tenantContext->set($this->restaurant);

        $this->cacheService = app(TenantCacheService::class);
        $this->cacheService->forgetMenu();

        $this->parserService = new NaturalLanguageOrderParserService(
            cacheService: $this->cacheService,
            tenantContext: $this->tenantContext,
        );
    }

    public function test_short_circuits_when_menu_digest_is_empty(): void
    {
        OrderParserAgent::fake();

        $data = new OrderParseData(text: '2 burgers and 1 fries');
        $result = $this->parserService->parse($data);

        $this->assertFalse($result->hasMatchedItems());
        $this->assertFalse($result->hasUnmatchedItems());
        $this->assertSame(0.0, $result->subtotal());
        $this->assertSame(0.0, $result->tax());
        $this->assertSame(0.0, $result->netTotal());
        $this->assertContains('Menu is currently empty. Please try again later.', $result->notes());

        OrderParserAgent::assertNeverPrompted();
    }

    public function test_parses_egyptian_arabic_order_with_server_authoritative_pricing(): void
    {
        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'description' => 'Beef burger patty',
        ]);

        $fries = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'French Fries',
            'price' => 40.00,
            'category' => ItemCategoryEnum::FOOD->value,
            'description' => 'Crispy potato fries',
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Burger',
                        'quantity' => 2,
                        'note' => 'من غير بصل',
                        'person' => null,
                        'confidence' => 0.98,
                    ],
                    [
                        'id' => $fries->id,
                        'name' => 'French Fries',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.95,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => ['خليه سخن لو سمحت'],
                'splits' => [],
                'language_detected' => 'ar_eg',
            ],
        ]);

        $data = new OrderParseData(
            text: 'ساندوتشين برجر من غير بصل وواحد بطاطس، وخليه سخن لو سمحت',
            preferredLanguage: 'ar_eg'
        );

        $result = $this->parserService->parse($data);

        $this->assertTrue($result->hasMatchedItems());
        $this->assertCount(2, $result->matchedItems);

        $this->assertSame(200.0, $result->matchedItems[0]['subtotal']);
        $this->assertSame(40.0, $result->matchedItems[1]['subtotal']);
        $this->assertSame(240.0, $result->subtotal());
        $this->assertSame(33.6, $result->tax());
        $this->assertSame(273.6, $result->netTotal());
        $this->assertSame('خليه سخن لو سمحت | Burger: من غير بصل', $result->combinedKitchenNote());
        $this->assertSame('ar_eg', $result->metadata->languageDetected);
    }

    public function test_tenant_security_guard_demotes_cross_tenant_item_ids(): void
    {
        $otherRestaurant = Restaurant::create([
            'name' => 'Sushi Bar',
            'slug' => 'sushi-bar',
            'email' => 'sushi@bar.com',
            'is_active' => true,
        ]);

        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $sushi = Item::create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Sushi Roll',
            'price' => 200.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Burger',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.95,
                    ],
                    [
                        'id' => $sushi->id,
                        'name' => 'Sushi Roll',
                        'quantity' => 2,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.90,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'en',
            ],
        ]);

        $data = new OrderParseData(text: '1 burger and 2 sushi rolls');
        $result = $this->parserService->parse($data);

        $this->assertCount(1, $result->matchedItems);
        $this->assertSame($burger->id, $result->matchedItems[0]['id']);
        $this->assertSame(100.0, $result->subtotal());

        $this->assertTrue($result->hasUnmatchedItems());
        $this->assertCount(1, $result->unmatchedItems);
        $this->assertSame('Sushi Roll', $result->unmatchedItems[0]['raw_text']);
        $this->assertSame('not_on_menu', $result->unmatchedItems[0]['reason']);
    }

    public function test_confidence_guard_demotes_ambiguous_items_below_threshold(): void
    {
        $item = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Soft Drink Can',
            'price' => 25.00,
            'category' => ItemCategoryEnum::BEVERAGE->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $item->id,
                        'name' => 'Soft Drink Can',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.50,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'ar_eg',
            ],
        ]);

        $data = new OrderParseData(text: 'حاجة ساقعة مش عارف نوعها');
        $result = $this->parserService->parse($data);

        $this->assertFalse($result->hasMatchedItems());
        $this->assertTrue($result->hasUnmatchedItems());
        $this->assertCount(1, $result->unmatchedItems);
        $this->assertSame('Soft Drink Can', $result->unmatchedItems[0]['raw_text']);
        $this->assertSame('ambiguous', $result->unmatchedItems[0]['reason']);
        $this->assertSame(['Soft Drink Can'], $result->unmatchedItems[0]['candidate_matches']);
        $this->assertSame(0.0, $result->subtotal());
    }

    public function test_deduplicates_duplicate_item_ids_and_merges_quantities_and_notes(): void
    {
        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Cheese Burger',
            'price' => 120.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Cheese Burger',
                        'quantity' => 2,
                        'note' => 'extra cheese',
                        'person' => null,
                        'confidence' => 0.95,
                    ],
                    [
                        'id' => $burger->id,
                        'name' => 'Cheese Burger',
                        'quantity' => 1,
                        'note' => 'no pickles',
                        'person' => null,
                        'confidence' => 0.90,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'en',
            ],
        ]);

        $data = new OrderParseData(text: '2 cheese burgers with extra cheese and 1 cheese burger with no pickles');
        $result = $this->parserService->parse($data);

        $this->assertCount(1, $result->matchedItems);
        $this->assertSame(3, $result->matchedItems[0]['quantity']);
        $this->assertSame(360.0, $result->matchedItems[0]['subtotal']);
        $this->assertSame('extra cheese, no pickles', $result->matchedItems[0]['note']);
        $this->assertSame(360.0, $result->subtotal());
        $this->assertSame(50.4, $result->tax());
        $this->assertSame(410.4, $result->netTotal());
    }

    public function test_deduplicates_duplicate_item_ids_with_person_attributed_notes(): void
    {
        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Smash Burger',
            'price' => 110.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Smash Burger',
                        'quantity' => 1,
                        'note' => 'extra cheese',
                        'person' => 'Ahmed',
                        'confidence' => 0.95,
                    ],
                    [
                        'id' => $burger->id,
                        'name' => 'Smash Burger',
                        'quantity' => 2,
                        'note' => 'no pickles',
                        'person' => 'Mona',
                        'confidence' => 0.90,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'en',
            ],
        ]);

        $data = new OrderParseData(text: 'Ahmed wants 1 smash burger extra cheese, Mona wants 2 smash burgers no pickles');
        $result = $this->parserService->parse($data);

        $this->assertCount(1, $result->matchedItems);
        $this->assertSame(3, $result->matchedItems[0]['quantity']);
        $this->assertSame('Ahmed: extra cheese, Mona: no pickles', $result->matchedItems[0]['note']);
        $this->assertSame('Ahmed, Mona', $result->matchedItems[0]['person']);
        $this->assertSame(330.0, $result->subtotal());
        $this->assertSame(46.2, $result->tax());
        $this->assertSame(376.2, $result->netTotal());
    }

    public function test_multi_person_order_calculates_bill_splits_authoritatively(): void
    {
        $shawarma = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Beef Shawarma',
            'price' => 90.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $crepe = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Chicken Crepe',
            'price' => 110.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $shawarma->id,
                        'name' => 'Beef Shawarma',
                        'quantity' => 2,
                        'note' => null,
                        'person' => 'Ahmed',
                        'confidence' => 0.96,
                    ],
                    [
                        'id' => $crepe->id,
                        'name' => 'Chicken Crepe',
                        'quantity' => 1,
                        'note' => null,
                        'person' => 'Mona',
                        'confidence' => 0.95,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'ar_eg',
            ],
        ]);

        $data = new OrderParseData(text: 'أحمد عايز 2 شاورما، ومنى عايزة 1 كريب');
        $result = $this->parserService->parse($data);

        $splits = $result->splits();
        $this->assertArrayHasKey('Ahmed', $splits);
        $this->assertArrayHasKey('Mona', $splits);

        $this->assertSame(180.0, $splits['Ahmed']['subtotal']);
        $this->assertSame($shawarma->id, $splits['Ahmed']['items'][0]['id']);
        $this->assertSame(2, $splits['Ahmed']['items'][0]['quantity']);

        $this->assertSame(110.0, $splits['Mona']['subtotal']);
        $this->assertSame($crepe->id, $splits['Mona']['items'][0]['id']);
        $this->assertSame(1, $splits['Mona']['items'][0]['quantity']);

        $this->assertSame(290.0, $result->subtotal());
        $this->assertSame(40.6, $result->tax());
        $this->assertSame(330.6, $result->netTotal());
    }

    public function test_fallback_raw_splits_excludes_low_confidence_and_cross_tenant_items(): void
    {
        $otherRestaurant = Restaurant::create([
            'name' => 'Pizza House',
            'slug' => 'pizza-house',
            'email' => 'pizza@house.com',
            'is_active' => true,
        ]);

        $burger = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Valid Burger',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $fries = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Ambiguous Fries',
            'price' => 40.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $pizza = Item::create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Cross Tenant Pizza',
            'price' => 150.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $burger->id,
                        'name' => 'Valid Burger',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.95,
                    ],
                    [
                        'id' => $fries->id,
                        'name' => 'Ambiguous Fries',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.40,
                    ],
                    [
                        'id' => $pizza->id,
                        'name' => 'Cross Tenant Pizza',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.95,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [
                    'Ahmed' => [
                        'items' => [
                            ['id' => $burger->id, 'name' => 'Valid Burger', 'quantity' => 1],
                        ],
                        'subtotal' => 100.0,
                    ],
                    'Mona' => [
                        'items' => [
                            ['id' => $fries->id, 'name' => 'Ambiguous Fries', 'quantity' => 1],
                        ],
                        'subtotal' => 40.0,
                    ],
                    'Tamer' => [
                        'items' => [
                            ['id' => $pizza->id, 'name' => 'Cross Tenant Pizza', 'quantity' => 1],
                        ],
                        'subtotal' => 150.0,
                    ],
                ],
                'language_detected' => 'en',
            ],
        ]);

        $data = new OrderParseData(text: '1 burger, 1 fries, 1 pizza');
        $result = $this->parserService->parse($data);

        $splits = $result->splits();

        // Valid item for Ahmed is included with authoritative pricing
        $this->assertArrayHasKey('Ahmed', $splits);
        $this->assertSame(100.0, $splits['Ahmed']['subtotal']);
        $this->assertCount(1, $splits['Ahmed']['items']);
        $this->assertSame($burger->id, $splits['Ahmed']['items'][0]['id']);

        // Low confidence item (< 0.65) was demoted and must be excluded from splits
        $this->assertArrayNotHasKey('Mona', $splits);

        // Cross-tenant item was demoted and must be excluded from splits
        $this->assertArrayNotHasKey('Tamer', $splits);
    }

    public function test_parses_audio_payload_input(): void
    {
        $pizza = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Margherita Pizza',
            'price' => 150.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $pizza->id,
                        'name' => 'Margherita Pizza',
                        'quantity' => 1,
                        'note' => null,
                        'person' => null,
                        'confidence' => 0.99,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'ar_eg',
            ],
        ]);

        $data = OrderParseData::fromWhatsApp(
            text: null,
            audioBase64: base64_encode('fake audio bytes'),
            audioMimeType: 'audio/ogg',
            audioDurationSeconds: 10,
            preferredLanguage: 'ar_eg'
        );

        $result = $this->parserService->parse($data);

        $this->assertTrue($result->hasMatchedItems());
        $this->assertSame(150.0, $result->subtotal());
        $this->assertSame(21.0, $result->tax());
        $this->assertSame(171.0, $result->netTotal());

        OrderParserAgent::assertPrompted(function ($prompt) {
            return $prompt->attachments->isNotEmpty();
        });
    }

    public function test_server_authoritative_pricing_overrides_ai_hallucinated_prices(): void
    {
        $steak = Item::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Ribeye Steak',
            'price' => 250.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        OrderParserAgent::fake([
            [
                'matched_items' => [
                    [
                        'id' => $steak->id,
                        'name' => 'Ribeye Steak',
                        'quantity' => 2,
                        'unit_price' => 5.0,
                        'subtotal' => 10.0,
                        'note' => 'medium rare',
                        'person' => null,
                        'confidence' => 0.99,
                    ],
                ],
                'unmatched_items' => [],
                'notes' => [],
                'splits' => [],
                'language_detected' => 'en',
            ],
        ]);

        $data = new OrderParseData(text: '2 ribeye steaks medium rare');
        $result = $this->parserService->parse($data);

        $this->assertSame(250.0, $result->matchedItems[0]['unit_price']);
        $this->assertSame(500.0, $result->matchedItems[0]['subtotal']);
        $this->assertSame(500.0, $result->subtotal());
        $this->assertSame(70.0, $result->tax());
        $this->assertSame(570.0, $result->netTotal());
    }
}
