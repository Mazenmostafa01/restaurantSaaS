<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuItemResource;
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
        $itemsByCategory = Item::with('attachments')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $menu = [];
        foreach ($itemsByCategory as $category => $categoryItems) {
            $menu[] = [
                'category' => $category,
                'items' => MenuItemResource::collection($categoryItems)->resolve(),
            ];
        }

        return response()->json(['menu' => $menu]);
    }
}
