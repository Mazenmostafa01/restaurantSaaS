<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the authenticated user's restaurant_id
 * and loads it into TenantContext so all subsequent Eloquent queries are
 * automatically scoped to that restaurant.
 *
 * Superadmin path: user has no restaurant_id → TenantContext stays empty
 *                  → TenantScope skips the WHERE clause → full visibility.
 *
 * Must run AFTER the 'auth' middleware so auth()->user() is available.
 */
class SetTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->restaurant_id !== null) {
            // Load the restaurant model once per request and cache it in the context
            $restaurant = Restaurant::find($user->restaurant_id);

            if ($restaurant && $restaurant->is_active) {
                $this->tenantContext->set($restaurant);

                return $next($request);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Your restaurant account is inactive.');
        }

        // Superadmins (restaurant_id = null) or unknown tenants pass through
        // with an empty TenantContext — TenantScope won't add a WHERE clause.
        return $next($request);
    }
}
