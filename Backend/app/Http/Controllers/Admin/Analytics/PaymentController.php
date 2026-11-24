<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\PaymentAnalyticsService;

class PaymentController extends Controller
{
    public function index(PaymentAnalyticsService $paymentSvc)
    {
        $kpis = [
            'successful' => $paymentSvc->getSuccessfulPaymentsCount(),
            'failed' => $paymentSvc->getFailedPaymentsCount(),
            'pending' => $paymentSvc->getPendingPaymentsCount(),
        ];

        $gatewayChart = $paymentSvc->getGatewayDistributionChartData();

        return view('admin.analytics.payments', compact('kpis', 'gatewayChart'));
    }
}
