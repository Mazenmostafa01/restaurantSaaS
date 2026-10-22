<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;
use Illuminate\Contracts\View\View;

class DashBoardController extends Controller
{
    public function __construct(private readonly DashboardAnalyticsService $analyticsService) {}

    public function __invoke(): View
    {
        $orders = $this->analyticsService->getYearlyOrderSummary();
        $yearlyData = $this->analyticsService->getYearlyMonthlySales();
        $topItems = $this->analyticsService->getTopSellingItems();

        return view('admin.dashboard.index', compact('orders', 'yearlyData', 'topItems'));
    }
}
