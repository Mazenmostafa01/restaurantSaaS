<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->restaurant_id !== null) {
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

        return $next($request);
    }
}
