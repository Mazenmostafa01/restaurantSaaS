<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class SaaSManagementService
{
    public function __construct(
        protected TenantCacheService $tenantCache
    ) {}

    /**
     * Aggregate high-level platform KPIs across all tenants.
     *
     * @return array{total_restaurants: int, active_restaurants: int, inactive_restaurants: int, trashed_restaurants: int, total_sales: float}
     */
    public function getPlatformStats(): array
    {
        return [
            'total_restaurants' => Restaurant::count(),
            'active_restaurants' => Restaurant::where('is_active', true)->count(),
            'inactive_restaurants' => Restaurant::where('is_active', false)->count(),
            'trashed_restaurants' => Restaurant::onlyTrashed()->count(),
            'total_sales' => (float) Order::withoutGlobalScopes()->whereNull('deleted_at')->sum('net'),
        ];
    }

    /**
     * Get paginated restaurants with aggregated metrics and filtering.
     */
    public function getPaginatedRestaurants(?string $search = null, string $status = 'all'): LengthAwarePaginator
    {
        $query = Restaurant::query();

        match ($status) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'trashed' => $query->onlyTrashed(),
            default => null,
        };

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return $query
            ->withCount([
                'orders' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'),
                'users' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->withSum(['orders as total_sales' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')], 'net')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Create a new Restaurant and its initial Admin user atomically.
     *
     * @param  array<string, mixed>  $restaurantData
     * @param  array{admin_name: string, admin_username: string, admin_email?: ?string, admin_password: string}  $adminData
     */
    public function createRestaurantWithAdmin(array $restaurantData, array $adminData, ?UploadedFile $logo = null): Restaurant
    {
        return DB::transaction(function () use ($restaurantData, $adminData, $logo): Restaurant {
            if ($logo !== null) {
                $restaurantData['logo_path'] = $logo->store('restaurants/logos', 'public');
            }

            $restaurantData['is_active'] = $restaurantData['is_active'] ?? true;

            $restaurant = Restaurant::create($restaurantData);

            $user = User::create([
                'restaurant_id' => $restaurant->id,
                'name' => $adminData['admin_name'],
                'username' => $adminData['admin_username'],
                'email' => $adminData['admin_email'] ?? null,
                'password' => $adminData['admin_password'],
                'need_password_change' => true,
            ]);

            Role::firstOrCreate(['name' => 'Admin']);
            $user->assignRole('Admin');

            return $restaurant;
        });
    }

    /**
     * Update restaurant profile details and logo.
     *
     * @param  array<string, mixed>  $restaurantData
     */
    public function updateRestaurant(Restaurant $restaurant, array $restaurantData, ?UploadedFile $logo = null): Restaurant
    {
        if ($logo !== null) {
            if ($restaurant->logo_path && Storage::disk('public')->exists($restaurant->logo_path)) {
                Storage::disk('public')->delete($restaurant->logo_path);
            }
            $restaurantData['logo_path'] = $logo->store('restaurants/logos', 'public');
        }

        $restaurant->update($restaurantData);
        $this->tenantCache->forgetRestaurant($restaurant);

        return $restaurant;
    }

    /**
     * Create an additional Admin user for a restaurant.
     *
     * @param  array{name: string, username: string, email?: ?string, password: string}  $adminData
     */
    public function createRestaurantAdmin(Restaurant $restaurant, array $adminData): User
    {
        return DB::transaction(function () use ($restaurant, $adminData): User {
            $user = User::create([
                'restaurant_id' => $restaurant->id,
                'name' => $adminData['name'],
                'username' => $adminData['username'],
                'email' => $adminData['email'] ?? null,
                'password' => $adminData['password'],
                'need_password_change' => true,
            ]);

            Role::firstOrCreate(['name' => 'Admin']);
            $user->assignRole('Admin');

            return $user;
        });
    }

    /**
     * Reset a restaurant Admin user's password and require password change on next login.
     */
    public function resetRestaurantAdminPassword(Restaurant $restaurant, User $admin, string $newPassword): void
    {
        if ((int) $admin->restaurant_id !== (int) $restaurant->id || ! $admin->hasRole('Admin')) {
            throw new DomainException('Admin user does not belong to this restaurant or does not have Admin role.');
        }

        $admin->update([
            'password' => $newPassword,
            'need_password_change' => true,
        ]);
    }

    /**
     * Toggle the active status of a restaurant.
     */
    public function toggleActive(Restaurant $restaurant): Restaurant
    {
        $restaurant->is_active = ! $restaurant->is_active;
        $restaurant->save();

        $this->tenantCache->forgetRestaurant($restaurant);

        return $restaurant;
    }

    /**
     * Archive (soft-delete) a restaurant.
     */
    public function archiveRestaurant(Restaurant $restaurant): void
    {
        $restaurant->is_active = false;
        $restaurant->save();
        $restaurant->delete();

        $this->tenantCache->forgetRestaurant($restaurant);
    }

    /**
     * Restore a soft-deleted restaurant.
     */
    public function restoreRestaurant(Restaurant $restaurant): void
    {
        $restaurant->restore();
        $this->tenantCache->forgetRestaurant($restaurant);
    }

    /**
     * Permanently delete a restaurant and cascade cleanup of all tenant data.
     */
    public function forceDeleteRestaurant(Restaurant $restaurant): void
    {
        if ($restaurant->is_active) {
            throw new DomainException('Restaurant must be deactivated before it can be permanently deleted.');
        }

        DB::transaction(function () use ($restaurant): void {
            $restaurantId = (int) $restaurant->id;

            $this->deleteTenantAttachments($restaurantId);

            if ($restaurant->logo_path && Storage::disk('public')->exists($restaurant->logo_path)) {
                Storage::disk('public')->delete($restaurant->logo_path);
            }

            $this->deleteTenantOrdersAndItems($restaurantId);
            $this->deleteTenantCustomersAndUsers($restaurantId);

            $this->tenantCache->forgetMenu($restaurantId);
            $this->tenantCache->forgetDashboard($restaurantId);
            $this->tenantCache->forgetRestaurant($restaurant);

            $restaurant->forceDelete();
        });
    }

    /**
     * Delete tenant attachments from disk and database in chunks.
     */
    private function deleteTenantAttachments(int $restaurantId): void
    {
        // Cross-tenant cleanup bypasses TenantScope per AGENTS.md to purge all tenant files
        Item::withoutGlobalScopes()
            ->withTrashed()
            ->where('restaurant_id', $restaurantId)
            ->with('attachments')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    foreach ($item->attachments as $attachment) {
                        if ($attachment->disk && $attachment->path && Storage::disk($attachment->disk)->exists($attachment->path)) {
                            Storage::disk($attachment->disk)->delete($attachment->path);
                        }
                        $attachment->delete();
                    }
                }
            });
    }

    /**
     * Delete tenant orders, order line items, and menu items in chunks.
     */
    private function deleteTenantOrdersAndItems(int $restaurantId): void
    {
        Order::withoutGlobalScopes()
            ->withTrashed()
            ->where('restaurant_id', $restaurantId)
            ->chunkById(200, function ($orders): void {
                $orderIds = $orders->pluck('id');
                DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
                Order::withoutGlobalScopes()->withTrashed()->whereIn('id', $orderIds)->forceDelete();
            });

        Item::withoutGlobalScopes()
            ->withTrashed()
            ->where('restaurant_id', $restaurantId)
            ->chunkById(200, function ($items): void {
                Item::withoutGlobalScopes()->withTrashed()->whereIn('id', $items->pluck('id'))->forceDelete();
            });
    }

    /**
     * Delete tenant customers, customer tokens, and users in chunks.
     */
    private function deleteTenantCustomersAndUsers(int $restaurantId): void
    {
        Customer::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->chunkById(200, function ($customers): void {
                foreach ($customers as $customer) {
                    $customer->tokens()->delete();
                    $customer->delete();
                }
            });

        User::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $user->syncRoles([]);
                    $user->delete();
                }
            });
    }
}
