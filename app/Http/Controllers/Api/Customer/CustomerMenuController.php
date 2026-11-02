<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\TenantCacheService;
use Illuminate\Http\JsonResponse;

class CustomerMenuController extends Controller
{
    public function __construct(private readonly TenantCacheService $tenantCache) {}

    /**
     * List all menu items for the current restaurant, grouped by category.
     * This is a public endpoint — no auth required.
     * TenantScope automatically filters by the restaurant set in middleware.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'menu' => $this->tenantCache->getCategorizedMenu(),
        ]);
    }
}
