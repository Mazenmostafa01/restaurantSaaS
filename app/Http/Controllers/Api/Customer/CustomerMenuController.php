<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;

class CustomerMenuController extends Controller
{
    /**
     * List all menu items for the current restaurant, grouped by category.
     * This is a public endpoint — no auth required.
     * TenantScope automatically filters by the restaurant set in middleware.
     */
    public function index(): JsonResponse
    {
        $items = Item::with('attachments')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $menu = [];
        foreach ($items as $category => $categoryItems) {
            $menu[] = [
                'category' => $category,
                'items'    => $categoryItems->map(fn ($item) => [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'price'       => number_format($item->price, 2),
                    'price_raw'   => (float) $item->price,
                    'description' => $item->description,
                    'category'    => $item->category,
                    'image'       => $item->attachments->where('is_primary', true)->first()?->url()
                                  ?? $item->attachments->first()?->url(),
                ]),
            ];
        }

        return response()->json(['menu' => $menu]);
    }
}
