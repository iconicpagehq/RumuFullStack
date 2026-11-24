<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\RevenueAnalyticsService;
use App\Services\Analytics\CustomerAnalyticsService;
use App\Services\Analytics\SubscriptionAnalyticsService;
use App\Services\Analytics\PaymentAnalyticsService;
use App\Services\Analytics\InvoiceAnalyticsService;

class DashboardController extends Controller
{
    public function index(
        RevenueAnalyticsService $revenueSvc,
        CustomerAnalyticsService $customerSvc,
        SubscriptionAnalyticsService $subscriptionSvc,
        PaymentAnalyticsService $paymentSvc,
        InvoiceAnalyticsService $invoiceSvc
    ) {
        $kpis = [
            'total_revenue' => $revenueSvc->getTotalRevenue(),
            'mrr' => $revenueSvc->getMRR(),
            'arr' => $revenueSvc->getARR(),
            'total_customers' => $customerSvc->getTotalCustomers(),
            'active_customers' => $customerSvc->getActiveCustomers(),
            'active_subscriptions' => $subscriptionSvc->getActiveSubscriptions(),
            'expired_subscriptions' => $subscriptionSvc->getExpiredSubscriptions(),
            'new_customers_month' => $customerSvc->getNewCustomersThisMonth(),
            'new_subscriptions_month' => $subscriptionSvc->getNewSubscriptionsThisMonth(),
            'successful_payments' => $paymentSvc->getSuccessfulPaymentsCount(),
            'failed_payments' => $paymentSvc->getFailedPaymentsCount(),
            'pending_payments' => $paymentSvc->getPendingPaymentsCount(),
            'total_invoices' => $invoiceSvc->getTotalInvoicesGenerated(),
            'revenue_growth' => 0, // Placeholder for Phase 13, could be calculated as (this month - last month) / last month * 100
        ];

        return view('admin.analytics.dashboard', compact('kpis'));
    }
}
