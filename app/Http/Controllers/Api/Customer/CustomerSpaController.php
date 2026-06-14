<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;

class CustomerSpaController extends Controller
{
    /**
     * Serve the React SPA shell for any customer-facing route.
     * React Router handles all client-side routing from here.
     */
    public function __invoke(Restaurant $restaurant)
    {
        if (! $restaurant->is_active) {
            abort(404, 'Restaurant not found.');
        }

        return view('customer.spa', [
            'restaurant' => $restaurant,
        ]);
    }
}
