<?php

namespace Tests\Feature;

use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaaSManagementTest extends TestCase
{
    use RefreshDatabase;

    private BusinessOwner $businessOwner;

    private Restaurant $restaurantA;

    private Restaurant $restaurantB;

    private User $adminA;

    private User $employeeA;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Employee']);

        $this->businessOwner = BusinessOwner::create([
            'name' => 'Platform Owner',
            'email' => 'owner@aklasaas.com',
            'password' => Hash::make('OwnerSecret123!'),
        ]);

        $this->restaurantA = Restaurant::create([
            'name' => 'Pizza Palace',
            'slug' => 'pizza-palace',
            'email' => 'contact@pizzapalace.com',
            'phone' => '01011112222',
            'address' => '123 Pizza St',
            'is_active' => true,
        ]);

        $this->restaurantB = Restaurant::create([
            'name' => 'Burger Barn',
            'slug' => 'burger-barn',
            'email' => 'contact@burgerbarn.com',
            'phone' => '01033334444',
            'address' => '456 Burger St',
            'is_active' => false,
        ]);

        app(TenantContext::class)->set($this->restaurantA);

        $this->adminA = User::create([
            'name' => 'Alice Admin',
            'username' => 'alice_admin',
            'email' => 'alice@pizzapalace.com',
            'password' => Hash::make('AdminPass123!'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => false,
        ]);
        $this->adminA->assignRole('Admin');

        $this->employeeA = User::create([
            'name' => 'Bob Employee',
            'username' => 'bob_employee',
            'email' => 'bob@pizzapalace.com',
            'password' => Hash::make('EmployeePass123!'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => false,
        ]);
        $this->employeeA->assignRole('Employee');

        app(TenantContext::class)->forget();
    }

    // ── 1. Guard & Table Isolation ──────────────────────────────────────────

    public function test_guests_cannot_access_saas_portal_and_are_redirected_to_saas_login(): void
    {
        $response = $this->get('/saas/restaurants');
        $response->assertRedirect(route('saas.login'));

        $createResponse = $this->get('/saas/restaurants/create');
        $createResponse->assertRedirect(route('saas.login'));

        $dashboardResponse = $this->get('/saas');
        $dashboardResponse->assertRedirect(route('saas.login'));
    }

    public function test_restaurant_staff_on_web_guard_cannot_access_saas_portal_and_are_redirected_to_saas_login(): void
    {
        // Admin on web guard
        $response = $this->actingAs($this->adminA, 'web')->get('/saas/restaurants');
        $response->assertRedirect(route('saas.login'));

        // Employee on web guard
        $responseEmployee = $this->actingAs($this->employeeA, 'web')->get('/saas/restaurants');
        $responseEmployee->assertRedirect(route('saas.login'));
    }

    public function test_business_owner_can_login_via_email_and_password_and_access_saas_portal(): void
    {
        $loginPage = $this->get(route('saas.login'));
        $loginPage->assertOk();

        $response = $this->post(route('saas.login.post'), [
            'email' => 'owner@aklasaas.com',
            'password' => 'OwnerSecret123!',
        ]);

        $response->assertRedirect(route('saas.restaurants.index'));
        $this->assertAuthenticatedAs($this->businessOwner, 'business_owner');

        $portalResponse = $this->actingAs($this->businessOwner, 'business_owner')->get(route('saas.restaurants.index'));
        $portalResponse->assertOk();
    }

    public function test_business_owner_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from(route('saas.login'))->post(route('saas.login.post'), [
            'email' => 'owner@aklasaas.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertRedirect(route('saas.login'));
        $response->assertSessionHasErrors('error');
        $this->assertGuest('business_owner');
    }

    public function test_restaurant_user_credentials_cannot_login_to_saas_portal(): void
    {
        // Alice exists in users table, but not in business_owners table
        $response = $this->from(route('saas.login'))->post(route('saas.login.post'), [
            'email' => 'alice@pizzapalace.com',
            'password' => 'AdminPass123!',
        ]);

        $response->assertRedirect(route('saas.login'));
        $response->assertSessionHasErrors('error');
        $this->assertGuest('business_owner');
    }

    public function test_business_owner_can_logout(): void
    {
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.logout'));

        $response->assertRedirect(route('saas.login'));
        $this->assertGuest('business_owner');
    }

    // ── 2. Dashboard KPIs & Aggregates ──────────────────────────────────────

    public function test_dashboard_displays_accurate_kpis_and_platform_sales(): void
    {
        // Set up orders on Restaurant A and Restaurant B
        Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-A-1',
            'restaurant_id' => $this->restaurantA->id,
            'price' => 100.00,
            'tax' => 14.00,
            'net' => 114.00,
            'type' => 'dine_in',
            'user_id' => $this->adminA->id,
        ]);

        Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-A-2',
            'restaurant_id' => $this->restaurantA->id,
            'price' => 200.00,
            'tax' => 28.00,
            'net' => 228.00,
            'type' => 'takeaway',
            'user_id' => $this->adminA->id,
        ]);

        Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-B-1',
            'restaurant_id' => $this->restaurantB->id,
            'price' => 300.00,
            'tax' => 42.00,
            'net' => 342.00,
            'type' => 'delivery',
            'user_id' => $this->adminA->id,
        ]);

        // Soft-deleted order (should NOT be included in platform sales sum)
        $deletedOrder = Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-DEL',
            'restaurant_id' => $this->restaurantA->id,
            'price' => 500.00,
            'tax' => 70.00,
            'net' => 570.00,
            'type' => 'dine_in',
            'user_id' => $this->adminA->id,
        ]);
        $deletedOrder->delete();

        // Total sales = 114 + 228 + 342 = 684.00
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->get(route('saas.restaurants.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_restaurants'] === 2
                && $stats['active_restaurants'] === 1
                && $stats['inactive_restaurants'] === 1
                && (float) $stats['total_sales'] === 684.00;
        });
    }

    public function test_dashboard_filters_restaurants_by_status_and_search(): void
    {
        $responseActive = $this->actingAs($this->businessOwner, 'business_owner')
            ->get(route('saas.restaurants.index', ['status' => 'active']));

        $responseActive->assertOk();
        $responseActive->assertSee('Pizza Palace');
        $responseActive->assertDontSee('Burger Barn');

        $responseInactive = $this->actingAs($this->businessOwner, 'business_owner')
            ->get(route('saas.restaurants.index', ['status' => 'inactive']));

        $responseInactive->assertOk();
        $responseInactive->assertSee('Burger Barn');
        $responseInactive->assertDontSee('Pizza Palace');

        $responseSearch = $this->actingAs($this->businessOwner, 'business_owner')
            ->get(route('saas.restaurants.index', ['q' => 'Burger']));

        $responseSearch->assertOk();
        $responseSearch->assertSee('Burger Barn');
        $responseSearch->assertDontSee('Pizza Palace');
    }

    // ── 3. Create Restaurant + Initial Admin ────────────────────────────────

    public function test_create_restaurant_atomically_creates_restaurant_and_initial_admin_with_empty_menu(): void
    {
        $payload = [
            'name' => 'Shawarma Station',
            'slug' => 'shawarma-station',
            'email' => 'owner@shawarma.com',
            'phone' => '01055556666',
            'address' => '789 Cairo Nile Rd',
            'is_active' => '1',
            'admin_name' => 'Sami Admin',
            'admin_username' => 'sami_shawarma',
            'admin_email' => 'sami@shawarma.com',
            'admin_password' => 'SuperPass123!',
            'admin_password_confirmation' => 'SuperPass123!',
        ];

        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.restaurants.store'), $payload);

        $response->assertRedirect(route('saas.restaurants.index'));

        // Assert Restaurant was created
        $this->assertDatabaseHas('restaurants', [
            'name' => 'Shawarma Station',
            'slug' => 'shawarma-station',
            'email' => 'owner@shawarma.com',
            'is_active' => 1,
        ]);

        $restaurant = Restaurant::where('slug', 'shawarma-station')->firstOrFail();

        // Assert 0 initial menu items
        $this->assertSame(0, Item::withoutGlobalScopes()->where('restaurant_id', $restaurant->id)->count());

        // Assert Admin user was created with need_password_change = true
        $this->assertDatabaseHas('users', [
            'username' => 'sami_shawarma',
            'restaurant_id' => $restaurant->id,
            'need_password_change' => 1,
        ]);

        $adminUser = User::withoutGlobalScopes()->where('username', 'sami_shawarma')->firstOrFail();
        $this->assertTrue($adminUser->hasRole('Admin'));
        $this->assertTrue(Hash::check('SuperPass123!', $adminUser->password));

        // Clear auth and reset guard to web before testing guest login to restaurant panel
        Auth::guard('business_owner')->logout();
        Auth::shouldUse('web');
        app(TenantContext::class)->forget();

        $loginResponse = $this->post('/login', [
            'email_or_username' => 'sami_shawarma',
            'password' => 'SuperPass123!',
        ]);
        $loginResponse->assertRedirect(route('password.change'));
        $this->assertAuthenticatedAs($adminUser, 'web');
    }

    public function test_create_restaurant_validates_required_fields_and_handles_reserved_slugs(): void
    {
        // Missing required fields
        $responseInvalid = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.restaurants.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'admin_name' => '',
                'admin_username' => '',
                'admin_password' => 'pass',
                'admin_password_confirmation' => 'mismatch',
            ]);

        $responseInvalid->assertSessionHasErrors(['name', 'email', 'admin_name', 'admin_username', 'admin_password']);

        // Attempt creating with duplicate slug
        $responseDuplicate = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.restaurants.store'), [
                'name' => 'Duplicate Pizza',
                'slug' => 'pizza-palace', // already exists
                'email' => 'newpizza@test.com',
                'phone' => '0100000001',
                'address' => 'Cairo',
                'admin_name' => 'Admin Test',
                'admin_username' => 'dup_admin',
                'admin_password' => 'Secret123!',
                'admin_password_confirmation' => 'Secret123!',
            ]);

        $responseDuplicate->assertSessionHasErrors('slug');

        // Creating with reserved slug 'saas' safely appends '-restaurant' to prevent collision
        $responseReserved = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.restaurants.store'), [
                'name' => 'SaaS Kitchen',
                'slug' => 'saas',
                'email' => 'kitchen@saas.com',
                'phone' => '01022223333',
                'address' => 'Cairo Tech Park',
                'admin_name' => 'Kitchen Admin',
                'admin_username' => 'kitchen_admin',
                'admin_password' => 'Secret123!',
                'admin_password_confirmation' => 'Secret123!',
            ]);

        $responseReserved->assertRedirect(route('saas.restaurants.index'));
        $this->assertDatabaseHas('restaurants', [
            'name' => 'SaaS Kitchen',
            'slug' => 'saas-restaurant',
        ]);
    }

    // ── 4. Toggle Active / Inactive & Isolation ─────────────────────────────

    public function test_toggle_active_toggles_status_and_blocks_staff_and_customer(): void
    {
        $this->assertTrue($this->restaurantA->is_active);

        // Toggle to Inactive
        $toggleResponse = $this->actingAs($this->businessOwner, 'business_owner')
            ->patch(route('saas.restaurants.toggle-active', $this->restaurantA));

        $toggleResponse->assertRedirect();
        $this->assertFalse($this->restaurantA->fresh()->is_active);

        // Staff access to admin dashboard should now be blocked (403)
        $staffResponse = $this->actingAs($this->adminA, 'web')->get('/');
        $staffResponse->assertForbidden();

        // Customer SPA access should return 404
        $spaResponse = $this->get('/'.$this->restaurantA->slug);
        $spaResponse->assertNotFound();

        // Customer API access should return 404
        $apiResponse = $this->getJson('/api/customer/'.$this->restaurantA->slug.'/menu');
        $apiResponse->assertNotFound();

        // Toggle back to Active
        $toggleBackResponse = $this->actingAs($this->businessOwner, 'business_owner')
            ->patch(route('saas.restaurants.toggle-active', $this->restaurantA));

        $toggleBackResponse->assertRedirect();
        $this->assertTrue($this->restaurantA->fresh()->is_active);

        // Staff access works again
        $staffResponseActive = $this->actingAs($this->adminA, 'web')->get('/');
        $staffResponseActive->assertOk();

        // Customer SPA works again
        $spaResponseActive = $this->get('/'.$this->restaurantA->slug);
        $spaResponseActive->assertOk();
    }

    // ── 5. Edit Restaurant & Admin Management ───────────────────────────────

    public function test_update_restaurant_updates_details_and_handles_slug(): void
    {
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->put(route('saas.restaurants.update', $this->restaurantA), [
                'name' => 'Pizza Palace Premium',
                'slug' => 'pizza-palace-premium',
                'email' => 'vip@pizzapalace.com',
                'phone' => '01099998888',
                'address' => '999 Nile Corniche',
                'is_active' => '1',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('restaurants', [
            'id' => $this->restaurantA->id,
            'name' => 'Pizza Palace Premium',
            'slug' => 'pizza-palace-premium',
            'email' => 'vip@pizzapalace.com',
        ]);
    }

    public function test_create_additional_restaurant_admin_assigns_role_and_need_password_change(): void
    {
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.restaurants.admins.store', $this->restaurantA), [
                'name' => 'Second Admin',
                'username' => 'second_admin',
                'email' => 'second@pizzapalace.com',
                'password' => 'SecondPass123!',
                'password_confirmation' => 'SecondPass123!',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'second_admin',
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => 1,
        ]);

        $secondAdmin = User::withoutGlobalScopes()->where('username', 'second_admin')->firstOrFail();
        $this->assertTrue($secondAdmin->hasRole('Admin'));
    }

    public function test_reset_restaurant_admin_password_updates_password_and_sets_need_password_change(): void
    {
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->put(route('saas.restaurants.admins.reset-password', [$this->restaurantA, $this->adminA]), [
                'password' => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ]);

        $response->assertRedirect();

        $this->adminA->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->adminA->password));
        $this->assertTrue((bool) $this->adminA->need_password_change);
    }

    public function test_reset_admin_password_scoped_bindings_blocks_user_from_different_restaurant(): void
    {
        // Admin of Restaurant B
        $adminB = User::create([
            'name' => 'Bob Admin',
            'username' => 'bob_admin',
            'email' => 'bob@burgerbarn.com',
            'password' => Hash::make('BobPass123!'),
            'restaurant_id' => $this->restaurantB->id,
            'need_password_change' => false,
        ]);
        $adminB->assignRole('Admin');

        // Attempt to reset Admin B's password through Restaurant A's route
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->put(route('saas.restaurants.admins.reset-password', [$this->restaurantA, $adminB]), [
                'password' => 'HackedPassword123!',
                'password_confirmation' => 'HackedPassword123!',
            ]);

        // scopeBindings() must return 404
        $response->assertNotFound();
    }

    // ── 6. Archive & Restore ────────────────────────────────────────────────

    public function test_archive_restaurant_deactivates_and_soft_deletes_and_restore_restores_it(): void
    {
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->delete(route('saas.restaurants.destroy', $this->restaurantA));

        $response->assertRedirect();

        $this->restaurantA->refresh();
        $this->assertTrue($this->restaurantA->trashed());
        $this->assertFalse($this->restaurantA->is_active);

        // Restore
        $restoreResponse = $this->actingAs($this->businessOwner, 'business_owner')
            ->post(route('saas.restaurants.restore', $this->restaurantA));

        $restoreResponse->assertRedirect();

        $this->restaurantA->refresh();
        $this->assertFalse($this->restaurantA->trashed());
    }

    // ── 7. Permanent Delete Guard & Cascade ─────────────────────────────────

    public function test_force_delete_is_rejected_when_restaurant_is_active(): void
    {
        $this->assertTrue($this->restaurantA->is_active);

        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->delete(route('saas.restaurants.force-destroy', $this->restaurantA));

        // Must redirect with error alert / not allowed
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('restaurants', ['id' => $this->restaurantA->id]);
    }

    public function test_force_delete_permanently_removes_inactive_restaurant_and_cascades_all_tenant_data(): void
    {
        // Deactivate restaurant B
        $this->assertFalse($this->restaurantB->is_active);

        // Create tenant records under Restaurant B
        $userB = User::create([
            'name' => 'Burger Admin',
            'username' => 'burger_admin',
            'email' => 'admin@burgerbarn.com',
            'password' => Hash::make('BurgerPass123!'),
            'restaurant_id' => $this->restaurantB->id,
        ]);
        $userB->assignRole('Admin');

        $customerB = Customer::create([
            'name' => 'Burger Customer',
            'phone_number' => '01012345678',
            'restaurant_id' => $this->restaurantB->id,
        ]);

        $itemB = Item::withoutGlobalScopes()->create([
            'name' => 'Cheeseburger',
            'price' => 50.00,
            'category' => 'Burgers',
            'restaurant_id' => $this->restaurantB->id,
        ]);

        $orderB = Order::withoutGlobalScopes()->create([
            'order_number' => 'ORD-B-CASCADE',
            'restaurant_id' => $this->restaurantB->id,
            'price' => 50.00,
            'tax' => 7.00,
            'net' => 57.00,
            'type' => 'takeaway',
            'user_id' => $userB->id,
            'customer_id' => $customerB->id,
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderB->id,
            'item_id' => $itemB->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Force delete Restaurant B
        $response = $this->actingAs($this->businessOwner, 'business_owner')
            ->delete(route('saas.restaurants.force-destroy', $this->restaurantB));

        $response->assertRedirect(route('saas.restaurants.index'));

        // Assert Restaurant B is permanently deleted
        $this->assertNull(Restaurant::withTrashed()->find($this->restaurantB->id));

        // Assert all cascading tenant records are wiped out
        $this->assertDatabaseMissing('users', ['restaurant_id' => $this->restaurantB->id]);
        $this->assertDatabaseMissing('customers', ['restaurant_id' => $this->restaurantB->id]);
        $this->assertDatabaseMissing('items', ['restaurant_id' => $this->restaurantB->id]);
        $this->assertDatabaseMissing('orders', ['restaurant_id' => $this->restaurantB->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $orderB->id]);
    }
}
