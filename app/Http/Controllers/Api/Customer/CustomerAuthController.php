<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    public function register(Request $request, TenantContext $tenant): JsonResponse
    {
        $email = (string) $request->input('email');
        $throttleKey = 'customer_register:'.$tenant->id().'|'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Too many registration attempts. Please try again in '.$seconds.' seconds.',
            ], 429, [
                'Retry-After' => $seconds,
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'address' => ['required', 'string', 'max:255'],
        ]);

        // Check uniqueness within this restaurant (composite unique)
        $exists = Customer::withoutGlobalScopes()
            ->where('restaurant_id', $tenant->id())
            ->where(function ($q) use ($validated) {
                $q->where('email', $validated['email'])
                    ->orWhere('phone_number', $validated['phone_number']);
            })
            ->exists();

        if ($exists) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'A customer with this email or phone already exists at this restaurant.',
            ], 422);
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => $validated['password'], // cast 'hashed' handles it
            'address' => $validated['address'] ?? null,
            'restaurant_id' => $tenant->id(),
        ]);

        // Log the customer in via the customer guard
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        RateLimiter::clear($throttleKey);

        return response()->json([
            'message' => 'Registration successful.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
        ], 201);
    }

    public function login(Request $request, TenantContext $tenant): JsonResponse
    {
        $email = (string) $request->input('email');
        $throttleKey = 'customer_login:'.$tenant->id().'|'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Too many login attempts. Please try again in '.$seconds.' seconds.',
            ], 429, [
                'Retry-After' => $seconds,
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find the customer scoped to this restaurant
        $customer = Customer::withoutGlobalScopes()
            ->where('restaurant_id', $tenant->id())
            ->where('email', $validated['email'])
            ->first();

        if (! $customer || ! Hash::check($validated['password'], $customer->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        RateLimiter::clear($throttleKey);

        return response()->json([
            'message' => 'Login successful.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'address' => $customer->address,
            ],
        ]);
    }
}
