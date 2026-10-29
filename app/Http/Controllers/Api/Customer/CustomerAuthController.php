<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerLoginRequest;
use App\Http\Requests\Customer\CustomerRegisterRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $throttleKey = $this->authService->buildThrottleKey('register', (string) $request->input('email'), (string) $request->ip());
        $validated = $request->validated();

        $existingCustomer = $this->authService->findExistingCustomerInTenant($validated);

        if ($existingCustomer) {
            if ($this->authService->canClaimShadowCustomer($existingCustomer, $validated)) {
                ['customer' => $customer, 'token' => $token] = $this->authService->claimShadowCustomer($existingCustomer, $validated, $throttleKey);

                return response()->json([
                    'message' => 'Registration successful. Your existing order history has been linked to your account.',
                    'token' => $token,
                    'customer' => new CustomerResource($customer),
                ], 201);
            }

            $this->authService->recordFailedAttempt($throttleKey);

            return response()->json([
                'message' => 'A customer with this email or phone already exists at this restaurant.',
            ], 422);
        }

        ['customer' => $customer, 'token' => $token] = $this->authService->registerCustomer($validated, $throttleKey);

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $throttleKey = $this->authService->buildThrottleKey('login', (string) $request->input('email'), (string) $request->ip());
        $validated = $request->validated();
        $customer = $this->authService->findAuthenticatedCustomer($validated['email'], $validated['password']);

        if (! $customer) {
            $this->authService->recordFailedAttempt($throttleKey);

            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'token' => $this->authService->issueLoginToken($customer, $throttleKey),
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'customer' => new CustomerResource($request->user()),
        ]);
    }
}
