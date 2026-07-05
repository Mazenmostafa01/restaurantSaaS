<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user();

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

    public function update(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'phone_number' => ['sometimes', 'string', 'max:20'],
            // allow empty string but convert to null later to avoid DB NOT NULL errors
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['phone_number']) && $validated['phone_number'] !== $customer->phone_number) {
            $phoneExists = Customer::withoutGlobalScopes()
                ->where('restaurant_id', $customer->restaurant_id)
                ->where('phone_number', $validated['phone_number'])
                ->where('id', '!=', $customer->id)
                ->exists();

            if ($phoneExists) {
                return response()->json([
                    'message' => 'This phone number is already in use.',
                    'errors' => ['phone_number' => ['This phone number is already in use at this restaurant.']],
                ], 422);
            }
        }

        // Filter out address if it's explicitly null to avoid violating DB NOT NULL constraints
        if (array_key_exists('address', $validated) && $validated['address'] === '') {
            $validated['address'] = null;
        }

        $customer->update($validated);

        return response()->json([
            'message' => 'Profile updated.',
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
