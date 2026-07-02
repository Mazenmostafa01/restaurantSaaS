<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class CustomerRestaurantController extends Controller
{
    /**
     * Return public restaurant info for the customer-facing app.
     * Used to display restaurant branding (name, logo, address).
     */
    public function show(Restaurant $restaurant): JsonResponse
    {
        return response()->json([
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'slug' => $restaurant->slug,
                'email' => $restaurant->email,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'logo' => $restaurant->logo_path
                    ? asset('storage/'.$restaurant->logo_path)
                    : null,
            ],
        ]);
    }
}
