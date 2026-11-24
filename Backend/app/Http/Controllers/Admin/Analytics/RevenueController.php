<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\RevenueAnalyticsService;
use App\Services\Analytics\CustomerAnalyticsService;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(RevenueAnalyticsService $revenueSvc, CustomerAnalyticsService $customerSvc, Request $request)
    {
        $kpis = [
            'total' => $revenueSvc->getTotalRevenue(),
            'mrr' => $revenueSvc->getMRR(),
            'arr' => $revenueSvc->getARR(),
            'arpc' => $revenueSvc->getARPC($customerSvc->getTotalCustomers()),
        ];

        $dailyChart = $revenueSvc->getDailyRevenueChartData(now()->subDays(30), now());
        $monthlyChart = $revenueSvc->getMonthlyRevenueChartData();

        return view('admin.analytics.revenue', compact('kpis', 'dailyChart', 'monthlyChart'));
    }
}
