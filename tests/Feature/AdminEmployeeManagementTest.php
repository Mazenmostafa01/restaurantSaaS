<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminEmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurantA;

    private Restaurant $restaurantB;

    private User $adminA;

    private User $employeeA;

    private User $adminB;

    private User $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Employee']);

        $this->restaurantA = Restaurant::create([
            'name' => 'Pizza Palace',
            'slug' => 'pizza-palace',
            'email' => 'admin@pizzapalace.com',
            'phone' => '1234567890',
            'address' => '101 Pizza St',
            'is_active' => true,
        ]);

        $this->restaurantB = Restaurant::create([
            'name' => 'Burger Barn',
            'slug' => 'burger-barn',
            'email' => 'admin@burgerbarn.com',
            'phone' => '0987654321',
            'address' => '202 Burger Ave',
            'is_active' => true,
        ]);

        app(TenantContext::class)->set($this->restaurantA);

        $this->adminA = User::create([
            'name' => 'Admin Alice',
            'username' => 'alice_admin',
            'email' => 'alice@pizzapalace.com',
            'password' => Hash::make('password123'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => false,
        ]);
        $this->adminA->assignRole('Admin');

        $this->employeeA = User::create([
            'name' => 'Employee Bob',
            'username' => 'bob_staff',
            'email' => 'bob@pizzapalace.com',
            'password' => Hash::make('password123'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => false,
        ]);
        $this->employeeA->assignRole('Employee');

        $this->adminB = User::create([
            'name' => 'Admin Charlie',
            'username' => 'charlie_admin',
            'email' => 'charlie@burgerbarn.com',
            'password' => Hash::make('password123'),
            'restaurant_id' => $this->restaurantB->id,
            'need_password_change' => false,
        ]);
        $this->adminB->assignRole('Admin');

        $this->employeeB = User::create([
            'name' => 'Employee Dave',
            'username' => 'dave_staff',
            'email' => 'dave@burgerbarn.com',
            'password' => Hash::make('password123'),
            'restaurant_id' => $this->restaurantB->id,
            'need_password_change' => false,
        ]);
        $this->employeeB->assignRole('Employee');
    }

    public function test_login_works_with_email(): void
    {
        $response = $this->post('/login', [
            'email_or_username' => 'alice@pizzapalace.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->adminA);
    }

    public function test_login_works_with_username(): void
    {
        $response = $this->post('/login', [
            'email_or_username' => 'alice_admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->adminA);
    }

    public function test_admin_can_view_employee_index_scoped_to_restaurant(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin Alice');
        $response->assertSee('Employee Bob');
        $response->assertSee('@alice_admin');
        $response->assertSee('@bob_staff');

        // Other restaurant employees must NOT be visible
        $response->assertDontSee('Admin Charlie');
        $response->assertDontSee('Employee Dave');
    }

    public function test_admin_can_view_create_page_with_roles_from_database(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('employees.create'));

        $response->assertStatus(200);
        $response->assertViewHas('roles', function ($roles): bool {
            return $roles->contains('Admin') && $roles->contains('Employee');
        });
    }

    public function test_admin_can_view_edit_page_with_roles_from_database(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('employees.edit', $this->employeeA));

        $response->assertStatus(200);
        $response->assertViewHas('roles', function ($roles): bool {
            return $roles->contains('Admin') && $roles->contains('Employee');
        });
    }

    public function test_employee_is_forbidden_from_accessing_employee_management(): void
    {
        $this->actingAs($this->employeeA)->get(route('employees.index'))->assertStatus(403);
        $this->actingAs($this->employeeA)->get(route('employees.create'))->assertStatus(403);
        $this->actingAs($this->employeeA)->post(route('employees.store'), [])->assertStatus(403);
        $this->actingAs($this->employeeA)->get(route('employees.edit', $this->employeeA))->assertStatus(403);
        $this->actingAs($this->employeeA)->put(route('employees.update', $this->employeeA), [])->assertStatus(403);
        $this->actingAs($this->employeeA)->delete(route('employees.destroy', $this->employeeA))->assertStatus(403);
    }

    public function test_admin_can_create_employee_with_role(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Chef Marco',
            'username' => 'marco_chef',
            'email' => 'marco@pizzapalace.com',
            'role' => 'Employee',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');

        $newEmployee = User::where('username', 'marco_chef')->first();
        $this->assertNotNull($newEmployee);
        $this->assertEquals('Chef Marco', $newEmployee->name);
        $this->assertEquals('marco@pizzapalace.com', $newEmployee->email);
        $this->assertEquals($this->restaurantA->id, $newEmployee->restaurant_id);
        $this->assertTrue($newEmployee->hasRole('Employee'));
        $this->assertTrue(Hash::check('secret1234', $newEmployee->password));
    }

    public function test_admin_can_create_employee_without_email(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Waiter Sam',
            'username' => 'sam_waiter',
            'email' => '',
            'role' => 'Employee',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('employees.index'));

        $newEmployee = User::where('username', 'sam_waiter')->first();
        $this->assertNotNull($newEmployee);
        $this->assertNull($newEmployee->email);
        $this->assertTrue($newEmployee->hasRole('Employee'));

        // Verify login works with username only, redirects to change password on first login
        $loginResponse = $this->post('/login', [
            'email_or_username' => 'sam_waiter',
            'password' => 'secret1234',
        ]);
        $loginResponse->assertRedirect(route('password.change'));
        $this->assertAuthenticatedAs($newEmployee);
    }

    public function test_admin_can_create_another_admin(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Manager Frank',
            'username' => 'frank_mgr',
            'email' => 'frank@pizzapalace.com',
            'role' => 'Admin',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('employees.index'));

        $newAdmin = User::where('username', 'frank_mgr')->first();
        $this->assertNotNull($newAdmin);
        $this->assertTrue($newAdmin->hasRole('Admin'));
    }

    public function test_custom_dynamic_role_is_rendered_and_assignable(): void
    {
        Role::firstOrCreate(['name' => 'Supervisor']);

        // Check create page renders the new dynamic role
        $createResponse = $this->actingAs($this->adminA)->get(route('employees.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Supervisor');

        // Check edit page renders the new dynamic role
        $editResponse = $this->actingAs($this->adminA)->get(route('employees.edit', $this->employeeA));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Supervisor');

        // Check store works with newly added dynamic role
        $response = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Supervisor Sam',
            'username' => 'sam_supervisor',
            'email' => 'supervisor@pizzapalace.com',
            'role' => 'Supervisor',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);
        $response->assertRedirect(route('employees.index'));

        $supervisor = User::where('username', 'sam_supervisor')->first();
        $this->assertNotNull($supervisor);
        $this->assertTrue($supervisor->hasRole('Supervisor'));
    }

    public function test_admin_can_update_employee_details_and_role(): void
    {
        $response = $this->actingAs($this->adminA)->put(route('employees.update', $this->employeeA), [
            'name' => 'Bob Updated',
            'username' => 'bob_promoted',
            'email' => 'bob_new@pizzapalace.com',
            'role' => 'Admin',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');

        $this->employeeA->refresh();
        $this->assertEquals('Bob Updated', $this->employeeA->name);
        $this->assertEquals('bob_promoted', $this->employeeA->username);
        $this->assertEquals('bob_new@pizzapalace.com', $this->employeeA->email);
        $this->assertTrue($this->employeeA->hasRole('Admin'));
        $this->assertTrue(Hash::check('newpassword123', $this->employeeA->password));
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $response = $this->actingAs($this->adminA)->put(route('employees.update', $this->adminA), [
            'name' => 'Admin Alice',
            'username' => 'alice_admin',
            'email' => 'alice@pizzapalace.com',
            'role' => 'Employee',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->adminA->refresh();
        $this->assertTrue($this->adminA->hasRole('Admin'));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminA)->delete(route('employees.destroy', $this->adminA));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->adminA->id]);
    }

    public function test_admin_can_delete_an_employee(): void
    {
        $response = $this->actingAs($this->adminA)->delete(route('employees.destroy', $this->employeeA));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $this->employeeA->id]);
    }

    public function test_admin_cannot_edit_or_delete_employee_of_another_restaurant(): void
    {
        $this->actingAs($this->adminA)->get(route('employees.edit', $this->employeeB))->assertStatus(404);

        $this->actingAs($this->adminA)->put(route('employees.update', $this->employeeB), [
            'name' => 'Hacked Name',
            'username' => 'hacked_user',
            'role' => 'Employee',
        ])->assertStatus(404);

        $this->actingAs($this->adminA)->delete(route('employees.destroy', $this->employeeB))->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $this->employeeB->id]);
    }

    public function test_employee_validation_enforces_unique_username_and_email(): void
    {
        // Existing username
        $response = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Duplicate Bob',
            'username' => 'bob_staff', // already exists
            'email' => 'unique@pizzapalace.com',
            'role' => 'Employee',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);
        $response->assertSessionHasErrors(['username']);

        // Existing email
        $response = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Duplicate Email',
            'username' => 'unique_user',
            'email' => 'bob@pizzapalace.com', // already exists
            'role' => 'Employee',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_employee_validation_rejects_non_existent_role(): void
    {
        // Store with invalid role
        $storeResponse = $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'Invalid Role User',
            'username' => 'invalid_role_user',
            'role' => 'NonExistentRole',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);
        $storeResponse->assertSessionHasErrors(['role']);

        // Update with invalid role
        $updateResponse = $this->actingAs($this->adminA)->put(route('employees.update', $this->employeeA), [
            'name' => 'Bob Updated',
            'username' => 'bob_staff',
            'role' => 'FakeRole123',
        ]);
        $updateResponse->assertSessionHasErrors(['role']);
    }

    public function test_newly_created_employee_has_need_password_change_set_to_true(): void
    {
        $this->actingAs($this->adminA)->post(route('employees.store'), [
            'name' => 'New Guy',
            'username' => 'new_guy',
            'email' => 'newguy@pizzapalace.com',
            'role' => 'Employee',
            'password' => 'initialpass123',
            'password_confirmation' => 'initialpass123',
        ]);

        $newEmployee = User::where('username', 'new_guy')->first();
        $this->assertNotNull($newEmployee);
        $this->assertTrue($newEmployee->need_password_change);
    }

    public function test_updating_employee_password_sets_need_password_change_to_true(): void
    {
        // First employee has need_password_change false
        $this->assertFalse($this->employeeA->need_password_change);

        $this->actingAs($this->adminA)->put(route('employees.update', $this->employeeA), [
            'name' => 'Employee Bob',
            'username' => 'bob_staff',
            'role' => 'Employee',
            'password' => 'admin_reset_pass123',
            'password_confirmation' => 'admin_reset_pass123',
        ]);

        $this->employeeA->refresh();
        $this->assertTrue($this->employeeA->need_password_change);
    }

    public function test_updating_employee_without_password_does_not_set_need_password_change(): void
    {
        $this->assertFalse($this->employeeA->need_password_change);

        $this->actingAs($this->adminA)->put(route('employees.update', $this->employeeA), [
            'name' => 'Bob No Pass Change',
            'username' => 'bob_staff',
            'role' => 'Employee',
        ]);

        $this->employeeA->refresh();
        $this->assertFalse($this->employeeA->need_password_change);
    }

    public function test_user_needing_password_change_is_redirected_on_login(): void
    {
        $user = User::create([
            'name' => 'Must Change',
            'username' => 'must_change_user',
            'email' => 'mustchange@pizzapalace.com',
            'password' => Hash::make('tempPass123'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => true,
        ]);
        $user->assignRole('Employee');

        $response = $this->post('/login', [
            'email_or_username' => 'must_change_user',
            'password' => 'tempPass123',
        ]);

        $response->assertRedirect(route('password.change'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_needing_password_change_is_blocked_from_admin_routes_by_middleware(): void
    {
        $user = User::create([
            'name' => 'Blocked User',
            'username' => 'blocked_user',
            'email' => 'blocked@pizzapalace.com',
            'password' => Hash::make('tempPass123'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => true,
        ]);
        $user->assignRole('Employee');

        // Cannot view home
        $this->actingAs($user)->get(route('home'))->assertRedirect(route('password.change'));

        // Cannot view items
        $this->actingAs($user)->get(route('items.index'))->assertRedirect(route('password.change'));

        // Can view change password page
        $this->actingAs($user)->get(route('password.change'))->assertStatus(200);

        // Can still log out
        $this->actingAs($user)->post(route('logout'))->assertRedirect('/login');
    }

    public function test_user_can_change_password_and_access_routes_normally(): void
    {
        $user = User::create([
            'name' => 'Changing User',
            'username' => 'changing_user',
            'email' => 'changing@pizzapalace.com',
            'password' => Hash::make('tempPass123'),
            'restaurant_id' => $this->restaurantA->id,
            'need_password_change' => true,
        ]);
        $user->assignRole('Employee');

        // Submit new password
        $response = $this->actingAs($user)->post(route('password.change.update'), [
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->need_password_change);
        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->password));

        // Now can access home and other routes
        $this->actingAs($user)->get(route('home'))->assertStatus(200);

        // Visiting password.change now redirects to home
        $this->actingAs($user)->get(route('password.change'))->assertRedirect(route('home'));
    }
}
