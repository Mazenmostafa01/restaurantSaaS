<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CustomerAuthService
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(private readonly TenantContext $tenant) {}

    public function buildThrottleKey(string $action, string $email, string $ip): string
    {
        $normalizedEmail = Str::lower(trim($email));

        return "customer_{$action}:{$this->tenant->id()}|{$normalizedEmail}|{$ip}";
    }

    public function ensureNotRateLimited(string $throttleKey, string $actionLabel): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw new HttpResponseException(
            response()->json([
                'message' => "Too many {$actionLabel} attempts. Please try again in {$seconds} seconds.",
            ], 429, ['Retry-After' => $seconds])
        );
    }

    /**
     * Bypass of TenantScope is intentional per docs/architecture.md §2.5:
     * customer auth checks uniqueness within the explicit tenant ID.
     *
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     */
    public function customerExistsInTenant(array $validated): bool
    {
        return Customer::withoutGlobalScopes()
            ->where('restaurant_id', $this->tenant->id())
            ->where(function ($query) use ($validated): void {
                $query->where('email', $validated['email'])
                    ->orWhere('phone_number', $validated['phone_number']);
            })
            ->exists();
    }

    /**
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     * @return array{customer: Customer, token: string}
     */
    public function registerCustomer(array $validated, string $throttleKey): array
    {
        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => $validated['password'],
            'address' => $validated['address'] ?? null,
        ]);

        $token = $this->rotateToken($customer);
        RateLimiter::clear($throttleKey);

        return ['customer' => $customer, 'token' => $token];
    }

    /**
     * Bypass of TenantScope is intentional per docs/architecture.md §2.5:
     * looks up customer by email within the active tenant ID.
     */
    public function findAuthenticatedCustomer(string $email, string $password): ?Customer
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('restaurant_id', $this->tenant->id())
            ->where('email', $email)
            ->first();

        if (! $customer || ! Hash::check($password, $customer->password)) {
            return null;
        }

        return $customer;
    }

    public function recordFailedAttempt(string $throttleKey): void
    {
        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
    }

    public function issueLoginToken(Customer $customer, string $throttleKey): string
    {
        $token = $this->rotateToken($customer);
        RateLimiter::clear($throttleKey);

        return $token;
    }

    private function rotateToken(Customer $customer): string
    {
        $customer->tokens()->delete();

        return $customer->createToken((string) $this->tenant->slug())->plainTextToken;
    }
}
