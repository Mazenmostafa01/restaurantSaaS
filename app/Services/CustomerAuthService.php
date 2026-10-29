<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
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
        $existing = $this->findExistingCustomerInTenant($validated);

        return $existing !== null && ! $this->canClaimShadowCustomer($existing, $validated);
    }

    /**
     * Look up existing customer matching email or phone in the active tenant.
     *
     * Bypass of TenantScope is intentional per docs/architecture.md §2.5:
     * customer auth checks uniqueness within the explicit tenant ID.
     *
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     */
    public function findExistingCustomerInTenant(array $validated): ?Customer
    {
        return $this->queryCustomerByEmailOrPhone($validated)->first();
    }

    /**
     * Bypass of TenantScope is intentional per docs/architecture.md §2.5:
     * locks matching customer row within the explicit tenant ID during registration.
     *
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     */
    public function findAndLockExistingCustomerInTenant(array $validated): ?Customer
    {
        return $this->queryCustomerByEmailOrPhone($validated)->lockForUpdate()->first();
    }

    /**
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     */
    private function queryCustomerByEmailOrPhone(array $validated): Builder
    {
        return Customer::withoutGlobalScopes()
            ->where('restaurant_id', $this->tenant->id())
            ->where(function ($query) use ($validated): void {
                $query->where('email', $validated['email'])
                    ->orWhere('phone_number', $validated['phone_number']);
            });
    }

    /**
     * Determines whether an existing customer record can be claimed as a shadow account.
     * A customer can claim the account if the account was created without a password (phone caller)
     * and their email does not conflict with another existing registered customer.
     *
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     */
    public function canClaimShadowCustomer(?Customer $existingCustomer, array $validated): bool
    {
        if (! $existingCustomer) {
            return false;
        }

        if ($existingCustomer->password !== null) {
            return false;
        }

        if ($existingCustomer->phone_number !== $validated['phone_number']) {
            return false;
        }

        $emailTakenByOther = Customer::withoutGlobalScopes()
            ->where('restaurant_id', $this->tenant->id())
            ->where('email', $validated['email'])
            ->where('id', '!=', $existingCustomer->id)
            ->exists();

        return ! $emailTakenByOther;
    }

    /**
     * Claim an existing shadow customer by assigning password, email, and updating address.
     *
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     * @return array{customer: Customer, token: string}
     */
    public function claimShadowCustomer(Customer $shadowCustomer, array $validated, string $throttleKey): array
    {
        $token = DB::transaction(function () use ($shadowCustomer, $validated): string {
            $shadowCustomer->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'address' => $validated['address'] ?? $shadowCustomer->address,
            ]);

            return $this->rotateToken($shadowCustomer);
        });

        RateLimiter::clear($throttleKey);

        return ['customer' => $shadowCustomer, 'token' => $token];
    }

    /**
     * @param  array{name: string, email: string, phone_number: string, password: string, address?: ?string}  $validated
     * @return array{customer: Customer, token: string}
     */
    public function registerCustomer(array $validated, string $throttleKey): array
    {
        [$customer, $token] = DB::transaction(function () use ($validated): array {
            $customer = Customer::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => $validated['password'],
                'address' => $validated['address'] ?? null,
            ]);

            return [$customer, $this->rotateToken($customer)];
        });

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

        if (! $customer || ! $customer->password || ! Hash::check($password, $customer->password)) {
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
