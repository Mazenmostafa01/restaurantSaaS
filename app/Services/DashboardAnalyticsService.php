<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function getYearlyOrderSummary(): ?Order
    {
        return Order::selectRaw('COALESCE(SUM(net), 0) as total_sales, COALESCE(SUM(tax), 0) as tax, COUNT(*) as total_orders')
            ->whereBetween('created_at', [now()->startOfYear(), now()])
            ->first();
    }

    /**
     * @return list<float>
     */
    public function getYearlyMonthlySales(): array
    {
        $monthExpr = $this->monthSqlExpression('created_at');

        $monthlySalesData = Order::selectRaw("{$monthExpr} as month, SUM(net) as total_sales")
            ->whereBetween('created_at', [now()->startOfYear(), now()])
            ->groupBy(DB::raw($monthExpr))
            ->orderBy(DB::raw($monthExpr))
            ->pluck('total_sales', 'month');

        $yearlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $yearlyData[] = (float) ($monthlySalesData->get($month, 0) ?? 0);
        }

        return $yearlyData;
    }

    /**
     * Uses SQL join and DB::raw aggregation for high performance on large datasets,
     * while explicitly scoping the joined orders table by restaurant_id and deleted_at
     * so MySQL utilizes the idx_orders_restaurant_date composite index.
     */
    public function getTopSellingItems(int $limit = 5): Collection
    {
        $tenantId = $this->tenant->id();

        return Item::select('items.name', DB::raw('SUM(order_items.quantity * items.price) as total_sales'))
            ->join('order_items', 'items.id', '=', 'order_items.item_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->when($tenantId !== null, fn ($query) => $query->where('orders.restaurant_id', $tenantId))
            ->whereNull('orders.deleted_at')
            ->whereBetween('orders.created_at', [now()->startOfYear(), now()])
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    private function monthSqlExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    }
}
