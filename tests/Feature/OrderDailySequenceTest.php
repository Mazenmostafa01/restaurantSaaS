<?php

namespace Tests\Feature;

use App\DTOs\OrderWriteData;
use App\DTOs\ParsedOrderData;
use App\DTOs\ParseMetadata;
use App\DTOs\ParsePricing;
use App\Enums\ItemCategoryEnum;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\OrderSequenceService;
use App\Services\OrderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderDailySequenceTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurantA;

    private Restaurant $restaurantB;

    private User $adminA;

    private Customer $customerA;

    private Item $itemA;

    private Item $itemB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin', 'web');

        $this->restaurantA = Restaurant::create([
            'name' => 'Cairo Bistro',
            'slug' => 'cairo-bistro',
            'email' => 'cairo@example.com',
            'is_active' => true,
        ]);

        $this->restaurantB = Restaurant::create([
            'name' => 'Alexandria Grill',
            'slug' => 'alexandria-grill',
            'email' => 'alex@example.com',
            'is_active' => true,
        ]);

        $this->adminA = User::create([
            'name' => 'Admin Cairo',
            'email' => 'admin@cairobistro.com',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurantA->id,
        ]);
        $this->adminA->assignRole('Admin');

        $this->customerA = Customer::create([
            'name' => 'Sara Ahmed',
            'email' => 'sara@example.com',
            'phone_number' => '01012345678',
            'password' => bcrypt('password123'),
            'restaurant_id' => $this->restaurantA->id,
        ]);

        $this->itemA = Item::withoutGlobalScopes()->create([
            'restaurant_id' => $this->restaurantA->id,
            'name' => 'Kofta',
            'price' => 100.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);

        $this->itemB = Item::withoutGlobalScopes()->create([
            'restaurant_id' => $this->restaurantB->id,
            'name' => 'Seafood',
            'price' => 150.00,
            'category' => ItemCategoryEnum::FOOD->value,
        ]);
    }

    public function test_admin_customer_and_ai_orders_share_daily_sequence_without_prefixes(): void
    {
        $this->travelTo('2026-11-18 10:00:00');
        $orderService = app(OrderService::class);
        app(TenantContext::class)->set($this->restaurantA);

        // 1. Admin POS order
        $this->actingAs($this->adminA)->post(route('orders.store'), [
            'type' => 'take_away',
            'items' => [
                $this->itemA->id => ['selected' => 'on', 'quantity' => 1],
            ],
        ])->assertRedirect(route('orders.create'));

        // 2. Customer SPA order
        $token = $this->customerA->createToken($this->restaurantA->slug)->plainTextToken;
        $this->postJson("/api/customer/{$this->restaurantA->slug}/orders", [
            'type' => 'take_away',
            'items' => [
                ['id' => $this->itemA->id, 'quantity' => 2],
            ],
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(201)
            ->assertJsonPath('order.order_number', '2');

        // 3. AI-parsed order via OrderWriteData::fromParsedOrder
        app(TenantContext::class)->set($this->restaurantA);
        $parsed = new ParsedOrderData(
            matchedItems: [
                [
                    'id' => $this->itemA->id,
                    'name' => $this->itemA->name,
                    'quantity' => 1,
                    'unit_price' => 100.0,
                    'subtotal' => 100.0,
                    'note' => null,
                    'person' => null,
                    'confidence' => 0.99,
                ],
            ],
            unmatchedItems: [],
            pricing: new ParsePricing(subtotal: 100.0, tax: 14.0, netTotal: 114.0),
            metadata: new ParseMetadata(notes: [], confidenceScore: 0.99, languageDetected: 'en')
        );
        $aiOrder = $orderService->createOrder(OrderWriteData::fromParsedOrder($parsed, customerId: $this->customerA->id));

        $orders = Order::withoutGlobalScopes()
            ->where('restaurant_id', $this->restaurantA->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $orders);
        $this->assertSame([1, 2, 3], $orders->pluck('daily_number')->all());
        $this->assertSame(['1', '2', '3'], $orders->pluck('order_number')->all());
        $this->assertSame(['2026-11-18', '2026-11-18', '2026-11-18'], $orders->pluck('order_date')->all());
        $this->assertSame(3, $aiOrder->daily_number);
    }

    public function test_sequence_resets_at_cairo_midnight(): void
    {
        $orderService = app(OrderService::class);
        app(TenantContext::class)->set($this->restaurantA);

        // 21:59 UTC is 23:59 Africa/Cairo on 2026-11-18
        $this->travelTo('2026-11-18 21:59:00');
        $order1 = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
        ));
        $this->assertSame('2026-11-18', $order1->order_date);
        $this->assertSame(1, $order1->daily_number);

        // 22:00 UTC is 00:00 Africa/Cairo on 2026-11-19 -> resets to 1
        $this->travelTo('2026-11-18 22:00:00');
        $order2 = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
        ));
        $this->assertSame('2026-11-19', $order2->order_date);
        $this->assertSame(1, $order2->daily_number);
        $this->assertSame('1', $order2->order_number);

        $order3 = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
        ));
        $this->assertSame('2026-11-19', $order3->order_date);
        $this->assertSame(2, $order3->daily_number);
    }

    public function test_restaurants_maintain_independent_daily_sequences(): void
    {
        $this->travelTo('2026-11-18 12:00:00');
        $orderService = app(OrderService::class);

        app(TenantContext::class)->set($this->restaurantA);
        $a1 = $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));
        $a2 = $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));

        app(TenantContext::class)->set($this->restaurantB);
        $b1 = $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemB->id => 1]));

        $this->assertSame(1, $a1->daily_number);
        $this->assertSame(2, $a2->daily_number);
        $this->assertSame(1, $b1->daily_number);
    }

    public function test_deleted_order_number_is_not_reused_and_edit_preserves_number(): void
    {
        $this->travelTo('2026-11-18 12:00:00');
        $orderService = app(OrderService::class);
        app(TenantContext::class)->set($this->restaurantA);

        $order1 = $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));
        $order2 = $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));

        $orderService->deleteOrder($order2);

        $order3 = $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));
        $this->assertSame(3, $order3->daily_number);

        $this->travelTo('2026-11-19 12:00:00');
        $updated = $orderService->updateOrder($order1, new OrderWriteData(
            type: 'delivery',
            itemQuantities: [$this->itemA->id => 2],
            note: 'Updated',
            userId: $this->adminA->id,
        ));

        $this->assertSame(1, $updated->daily_number);
        $this->assertSame('1', $updated->order_number);
        $this->assertSame('2026-11-18', $updated->order_date);
    }

    public function test_failed_transaction_rolls_back_counter_with_no_gap(): void
    {
        $this->travelTo('2026-11-18 12:00:00');
        $orderService = app(OrderService::class);
        app(TenantContext::class)->set($this->restaurantA);

        $first = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
        ));
        $this->assertSame(1, $first->daily_number);

        // Simulate failure inside the transaction after claimNext() has incremented the counter
        Event::listen('eloquent.created: '.Order::class, static function (): void {
            throw new RuntimeException('Simulated DB failure after order insert');
        });

        try {
            $orderService->createOrder(new OrderWriteData(
                type: 'take_away',
                itemQuantities: [$this->itemA->id => 1],
                userId: $this->adminA->id,
            ));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated DB failure after order insert', $e->getMessage());
        } finally {
            Event::forget('eloquent.created: '.Order::class);
        }

        // Next order must still receive 2 (no gap!)
        $second = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
        ));
        $this->assertSame(2, $second->daily_number);
    }

    public function test_admin_order_search_and_date_filter(): void
    {
        $orderService = app(OrderService::class);
        app(TenantContext::class)->set($this->restaurantA);

        // Yesterday orders: #1, #2
        $this->travelTo('2026-11-17 12:00:00');
        $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));
        $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));

        // Today orders: #1, #2 (with customer phone 01012345678), #3
        $this->travelTo('2026-11-18 12:00:00');
        $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));
        $todayOrder2 = $orderService->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
            customerId: $this->customerA->id,
        ));
        $orderService->createOrder(new OrderWriteData(type: 'take_away', itemQuantities: [$this->itemA->id => 1], userId: $this->adminA->id));

        // Search "#2" defaults to today and returns only today's #2
        $response = $this->actingAs($this->adminA)->get(route('orders.index', ['q' => '#2']));
        $response->assertOk();
        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertSame($todayOrder2->id, $orders->first()->id);

        // Search by customer phone starting with 0 still works
        $phoneResponse = $this->actingAs($this->adminA)->get(route('orders.index', ['q' => '01012345678']));
        $phoneResponse->assertOk();
        $this->assertCount(1, $phoneResponse->viewData('orders'));

        // Date filter for yesterday returns yesterday's orders sorted by daily_number DESC
        $dateResponse = $this->actingAs($this->adminA)->get(route('orders.index', ['date' => '2026-11-17']));
        $dateResponse->assertOk();
        $yesterdayOrders = $dateResponse->viewData('orders');
        $this->assertSame([2, 1], $yesterdayOrders->pluck('daily_number')->all());
    }

    public function test_backfill_command_numbers_legacy_orders_per_cairo_day_and_seeds_counters(): void
    {
        // Roll back phase 2 enforcement migration so NULL order_date/daily_number can be inserted
        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations/2026_11_18_102607_enforce_daily_number_on_orders_table.php',
            '--no-interaction' => true,
        ]);

        // Legacy order at 21:59 UTC (23:59 Cairo on 2026-11-17)
        $legacyDay1 = DB::table('orders')->insertGetId([
            'restaurant_id' => $this->restaurantA->id,
            'order_number' => '261117-AAA111',
            'order_date' => null,
            'daily_number' => null,
            'price' => 50.00,
            'tax' => 7.00,
            'net' => 57.00,
            'type' => 'take_away',
            'created_at' => '2026-11-17 21:59:00',
            'updated_at' => '2026-11-17 21:59:00',
        ]);

        // Legacy orders at 22:05 UTC (00:05 Cairo on 2026-11-18) and 10:00 UTC (12:00 Cairo on 2026-11-18)
        $legacyDay2First = DB::table('orders')->insertGetId([
            'restaurant_id' => $this->restaurantA->id,
            'order_number' => '261118-BBB222',
            'order_date' => null,
            'daily_number' => null,
            'price' => 60.00,
            'tax' => 8.40,
            'net' => 68.40,
            'type' => 'take_away',
            'created_at' => '2026-11-17 22:05:00',
            'updated_at' => '2026-11-17 22:05:00',
        ]);

        $legacyDay2Second = DB::table('orders')->insertGetId([
            'restaurant_id' => $this->restaurantA->id,
            'order_number' => '261118-CCC333',
            'order_date' => null,
            'daily_number' => null,
            'price' => 70.00,
            'tax' => 9.80,
            'net' => 79.80,
            'type' => 'take_away',
            'created_at' => '2026-11-18 10:00:00',
            'updated_at' => '2026-11-18 10:00:00',
        ]);

        // Run backfill
        $this->artisan('orders:backfill-daily-numbers')->assertExitCode(0);

        // Idempotency: running a second time succeeds and does not alter assigned numbers
        $this->artisan('orders:backfill-daily-numbers')->assertExitCode(0);

        // Re-apply phase 2 enforcement migration
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_11_18_102607_enforce_daily_number_on_orders_table.php',
            '--no-interaction' => true,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $legacyDay1,
            'order_number' => '261117-AAA111',
            'order_date' => '2026-11-17',
            'daily_number' => 1,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $legacyDay2First,
            'order_number' => '261118-BBB222',
            'order_date' => '2026-11-18',
            'daily_number' => 1,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $legacyDay2Second,
            'order_number' => '261118-CCC333',
            'order_date' => '2026-11-18',
            'daily_number' => 2,
        ]);

        // New order on 2026-11-18 continues from 3
        $this->travelTo('2026-11-18 15:00:00');
        app(TenantContext::class)->set($this->restaurantA);
        $nextOrder = app(OrderService::class)->createOrder(new OrderWriteData(
            type: 'take_away',
            itemQuantities: [$this->itemA->id => 1],
            userId: $this->adminA->id,
        ));
        $this->assertSame(3, $nextOrder->daily_number);
        $this->assertSame('3', $nextOrder->order_number);
        $this->assertSame(3, app(OrderSequenceService::class)->claimNext($this->restaurantA->id, '2026-11-18') - 1);
    }
}
