<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the {restaurant} route parameter (slug)
 * for customer-facing API routes.
 *
 * Unlike SetTenantContext (admin), which reads restaurant_id from the
 * authenticated user, this middleware reads the restaurant from the URL.
 * This allows public routes (menu browsing) to work without authentication.
 */
class SetCustomerTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->route('restaurant');

        // Route model binding gives us a Restaurant instance when using {restaurant:slug}
        if (! $restaurant instanceof Restaurant) {
            $restaurant = Restaurant::where('slug', $restaurant)->first();
        }

        if (! $restaurant || ! $restaurant->is_active) {
            return response()->json([
                'message' => 'Restaurant not found or inactive.',
            ], 404);
        }

        $this->tenantContext->set($restaurant);

        // Prevent cross-tenant session pollution:
        // If the route is protected for customers, ensure the authenticated customer belongs to this restaurant.
        $middlewares = $request->route()->middleware();
        if (in_array('auth:customer', $middlewares)) {
            $customer = $request->user('customer');
            if ($customer && $customer->restaurant_id !== $restaurant->id) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        }

        return $next($request);
    }
}
