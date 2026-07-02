<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCustomerTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->route('restaurant');

        if (! $restaurant instanceof Restaurant) {
            $restaurant = Restaurant::where('slug', $restaurant)->first();
        }

        if (! $restaurant || ! $restaurant->is_active) {
            return response()->json([
                'message' => 'Restaurant not found or inactive.',
            ], 404);
        }

        $this->tenantContext->set($restaurant);

        $middlewares = $request->route()->middleware();
        if (in_array('auth:customer', $middlewares)) {
            $customer = $request->user('customer');
            if ($customer && (int) $customer->restaurant_id !== (int) $restaurant->id) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        }

        return $next($request);
    }
}
