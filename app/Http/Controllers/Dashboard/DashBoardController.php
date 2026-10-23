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
        $dashboardData = $this->analyticsService->getDashboardData();

        return view('admin.dashboard.index', [
            'orders' => $dashboardData['orders'],
            'yearlyData' => $dashboardData['yearlyData'],
            'topItems' => $dashboardData['topItems'],
        ]);
    }
}
