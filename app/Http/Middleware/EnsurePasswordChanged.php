<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->need_password_change) {
            if (! $request->routeIs('password.change', 'password.change.update', 'logout')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Password change required.'], 403);
                }

                return redirect()->route('password.change');
            }
        } elseif ($user && ! $user->need_password_change) {
            if ($request->routeIs('password.change', 'password.change.update')) {
                return redirect()->route('home');
            }
        }

        return $next($request);
    }
}
