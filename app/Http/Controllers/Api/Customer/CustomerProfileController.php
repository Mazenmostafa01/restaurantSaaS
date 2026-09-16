<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'phone_number' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('customers', 'phone_number')
                    ->where('restaurant_id', $customer->restaurant_id)
                    ->ignore($customer->id),
            ],
            // allow empty string but convert to null later to avoid DB NOT NULL errors
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ], [
            'phone_number.unique' => 'This phone number is already in use at this restaurant.',
        ]);

        // Filter out address if it's explicitly empty string to avoid violating DB NOT NULL constraints
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
