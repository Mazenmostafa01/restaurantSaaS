<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaaS\ResetRestaurantAdminPasswordRequest;
use App\Http\Requests\SaaS\StoreRestaurantAdminRequest;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\SaaSManagementService;
use Illuminate\Http\RedirectResponse;

class SaaSRestaurantAdminController extends Controller
{
    public function __construct(
        protected SaaSManagementService $service
    ) {}

    public function store(StoreRestaurantAdminRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $this->service->createRestaurantAdmin($restaurant, $request->validated());

        return back()->with('success', 'Restaurant Admin added successfully.');
    }

    public function updatePassword(ResetRestaurantAdminPasswordRequest $request, Restaurant $restaurant, User $user): RedirectResponse
    {
        $this->service->resetRestaurantAdminPassword($restaurant, $user, $request->validated('password'));

        return back()->with('success', 'Admin password reset successfully.');
    }
}
