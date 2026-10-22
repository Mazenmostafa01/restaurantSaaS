<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerProfileUpdateRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'customer' => new CustomerResource($request->user()),
        ]);
    }

    public function update(CustomerProfileUpdateRequest $request): JsonResponse
    {
        $customer = $request->user();
        $attributes = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null
        );

        $customer->update($attributes);

        return response()->json([
            'message' => 'Profile updated.',
            'customer' => new CustomerResource($customer),
        ]);
    }
}
