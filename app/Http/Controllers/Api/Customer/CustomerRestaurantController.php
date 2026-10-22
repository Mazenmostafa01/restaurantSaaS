<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
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
            'restaurant' => new RestaurantResource($restaurant),
        ]);
    }
}
