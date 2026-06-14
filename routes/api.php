<?php

use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerMenuController;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\Customer\CustomerProfileController;
use App\Http\Controllers\Api\Customer\CustomerRestaurantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer API Routes
|--------------------------------------------------------------------------
|
| All customer-facing API routes are prefixed with /api/customer/{restaurant:slug}
| The {restaurant:slug} parameter is resolved by SetCustomerTenantContext middleware
| which sets the TenantContext so all Eloquent queries are scoped automatically.
|
*/

// ── Public routes (no auth required) ─────────────────────────────────────────
Route::prefix('customer/{restaurant:slug}')
    ->middleware('customer-tenant')
    ->group(function () {

        // Restaurant info (branding, address, etc.)
        Route::get('/restaurant', [CustomerRestaurantController::class, 'show']);

        // Menu (items grouped by category)
        Route::get('/menu', [CustomerMenuController::class, 'index']);

        // Auth
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/login', [CustomerAuthController::class, 'login']);
    });

// ── Authenticated customer routes ────────────────────────────────────────────
Route::prefix('customer/{restaurant:slug}')
    ->middleware(['auth:customer', 'customer-tenant'])
    ->group(function () {

        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/user', [CustomerAuthController::class, 'user']);

        // Profile
        Route::get('/profile', [CustomerProfileController::class, 'show']);
        Route::put('/profile', [CustomerProfileController::class, 'update']);

        // Orders
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::post('/orders', [CustomerOrderController::class, 'store']);
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show']);
    });

// ── Admin API (default Sanctum user route) ───────────────────────────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
