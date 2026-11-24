<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\CustomerAnalyticsService;

class CustomerController extends Controller
{
    public function index(CustomerAnalyticsService $customerSvc)
    {
        $kpis = [
            'total' => $customerSvc->getTotalCustomers(),
            'active' => $customerSvc->getActiveCustomers(),
            'new_this_month' => $customerSvc->getNewCustomersThisMonth(),
        ];

        $planChart = $customerSvc->getPlanDistributionChartData();

        return view('admin.analytics.customers', compact('kpis', 'planChart'));
    }
}
