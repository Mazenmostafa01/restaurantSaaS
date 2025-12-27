<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    public function __invoke()
    {
        $orders = Order::selectRaw('SUM(net) as total_sales, SUM(tax) as tax, COUNT(*) as total_orders')
            ->whereBetween('created_at', [now()->startOfYear(), now()])->first();

        // Fetch monthly sales data for yearly chart
        $monthlySalesData = Order::selectRaw('MONTH(created_at) as month, SUM(net) as total_sales')
            ->whereBetween('created_at', [now()->startOfYear(), now()])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->pluck('total_sales', 'month');

        // Build yearly array with all 12 months
        $yearlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $yearlyData[] = (float) ($monthlySalesData->get($month, 0) ?? 0);
        }

        return view('admin.dashboard.index', compact('orders', 'yearlyData'));
    }
}
