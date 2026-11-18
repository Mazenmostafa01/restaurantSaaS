<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaaS\StoreRestaurantRequest;
use App\Http\Requests\SaaS\UpdateRestaurantRequest;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\SaaSManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaaSRestaurantController extends Controller
{
    public function __construct(
        protected SaaSManagementService $service
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status', 'all')->toString();
        $search = $request->string('q', '')->toString();

        $stats = $this->service->getPlatformStats();
        $restaurants = $this->service->getPaginatedRestaurants(
            search: $search !== '' ? $search : null,
            status: $status,
        );

        return view('saas.restaurants.index', compact('stats', 'restaurants', 'status', 'search'));
    }

    public function create(): View
    {
        return view('saas.restaurants.create');
    }

    public function store(StoreRestaurantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $restaurantData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        $adminData = [
            'admin_name' => $validated['admin_name'],
            'admin_username' => $validated['admin_username'],
            'admin_email' => $validated['admin_email'] ?? null,
            'admin_password' => $validated['admin_password'],
        ];

        $this->service->createRestaurantWithAdmin($restaurantData, $adminData, $request->file('logo'));

        return redirect()->route('saas.restaurants.index')->with('success', 'Restaurant and initial Admin created successfully.');
    }

    public function edit(Restaurant $restaurant): View
    {
        $restaurant->loadCount([
            'orders' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'),
            'users' => fn ($q) => $q->withoutGlobalScopes(),
        ]);
        $restaurant->loadSum(['orders as total_sales' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')], 'net');

        $admins = User::withoutGlobalScopes()
            ->where('restaurant_id', $restaurant->id)
            ->role('Admin')
            ->orderByDesc('id')
            ->get();

        return view('saas.restaurants.edit', compact('restaurant', 'admins'));
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validated();

        $restaurantData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $restaurant->is_active,
        ];

        $this->service->updateRestaurant($restaurant, $restaurantData, $request->file('logo'));

        return redirect()->route('saas.restaurants.edit', $restaurant)->with('success', 'Restaurant updated successfully.');
    }

    public function toggleActive(Restaurant $restaurant): RedirectResponse
    {
        $this->service->toggleActive($restaurant);

        $statusText = $restaurant->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Restaurant {$statusText} successfully.");
    }

    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        $this->service->archiveRestaurant($restaurant);

        return redirect()->route('saas.restaurants.index')->with('success', 'Restaurant archived successfully.');
    }

    public function restore(Restaurant $restaurant): RedirectResponse
    {
        $this->service->restoreRestaurant($restaurant);

        return back()->with('success', 'Restaurant restored successfully.');
    }

    public function forceDestroy(Restaurant $restaurant): RedirectResponse
    {
        try {
            $this->service->forceDeleteRestaurant($restaurant);

            return redirect()->route('saas.restaurants.index')->with('success', 'Restaurant permanently deleted.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
