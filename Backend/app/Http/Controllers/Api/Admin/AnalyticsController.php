<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AnalyticsResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\UserResource;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\User;
use App\Services\Analytics\CustomerAnalyticsService;
use App\Services\Analytics\InvoiceAnalyticsService;
use App\Services\Analytics\PaymentAnalyticsService;
use App\Services\Analytics\ReportService;
use App\Services\Analytics\RevenueAnalyticsService;
use App\Services\Analytics\SubscriptionAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends ApiController
{
    public function dashboard(
        RevenueAnalyticsService $revenue,
        CustomerAnalyticsService $customers,
        SubscriptionAnalyticsService $subscriptions,
        PaymentAnalyticsService $payments,
        InvoiceAnalyticsService $invoices
    ) {
        $data = [
            'kpis' => [
                'total_revenue' => $revenue->getTotalRevenue(),
                'mrr' => $revenue->getMRR(),
                'arr' => $revenue->getARR(),
                'total_customers' => $customers->getTotalCustomers(),
                'active_customers' => $customers->getActiveCustomers(),
                'active_subscriptions' => $subscriptions->getActiveSubscriptions(),
                'expired_subscriptions' => $subscriptions->getExpiredSubscriptions(),
                'new_customers_month' => $customers->getNewCustomersThisMonth(),
                'new_subscriptions_month' => $subscriptions->getNewSubscriptionsThisMonth(),
                'successful_payments' => $payments->getSuccessfulPaymentsCount(),
                'failed_payments' => $payments->getFailedPaymentsCount(),
                'pending_payments' => $payments->getPendingPaymentsCount(),
                'total_invoices' => $invoices->getTotalInvoicesGenerated(),
            ],
            'revenue_monthly' => $revenue->getMonthlyRevenueChartData(),
            'revenue_daily' => $revenue->getDailyRevenueChartData(now()->subDays(29), now()),
            'plan_distribution' => $customers->getPlanDistributionChartData(),
            'payment_methods' => $payments->getGatewayDistributionChartData(),
            'invoice_status' => $invoices->getInvoiceStatusDistributionChartData(),
            'recent_payments' => PaymentResource::collection(
                Payment::with(['subscription.user', 'coupon'])->latest()->limit(6)->get()
            ),
            'recent_realtors' => UserResource::collection(
                User::where('role', 'realtor')->with('plan')->latest()->limit(6)->get()
            ),
            'enterprise_kpis' => [
                'total' => Enterprise::count(),
                'active' => Enterprise::where('status', 'active')->count(),
                'suspended' => Enterprise::where('status', 'suspended')->count(),
                'members' => EnterpriseMembership::where('status', EnterpriseMembership::STATUS_ACTIVE)->count(),
                'leads_total' => Lead::whereNotNull('enterprise_id')->count(),
                'leads_this_month' => Lead::whereNotNull('enterprise_id')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'enterprise_plan_distribution' => Enterprise::whereNotNull('plan_id')
                ->with('plan:id,name')
                ->get()
                ->groupBy('plan_id')
                ->map(fn ($group) => [
                    'name' => $group->first()->plan?->name ?? 'Unassigned',
                    'value' => $group->count(),
                ])
                ->values(),
        ];

        return $this->success(new AnalyticsResource($data));
    }

    public function enterprises()
    {
        $months = [];
        $newEnterprises = [];
        $enterpriseLeads = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            $newEnterprises[] = Enterprise::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $enterpriseLeads[] = Lead::whereNotNull('enterprise_id')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        $byPlan = Enterprise::with('plan:id,name')
            ->get()
            ->groupBy('plan_id')
            ->map(fn ($group) => [
                'name' => $group->first()->plan?->name ?? 'Unassigned',
                'value' => $group->count(),
            ])
            ->values();

        return $this->success([
            'kpis' => [
                'total' => Enterprise::count(),
                'active' => Enterprise::where('status', 'active')->count(),
                'suspended' => Enterprise::where('status', 'suspended')->count(),
                'members' => EnterpriseMembership::where('status', EnterpriseMembership::STATUS_ACTIVE)->count(),
                'leads_total' => Lead::whereNotNull('enterprise_id')->count(),
                'leads_this_month' => Lead::whereNotNull('enterprise_id')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'months' => $months,
            'new_enterprises_monthly' => $newEnterprises,
            'leads_monthly' => $enterpriseLeads,
            'plan_distribution' => $byPlan,
        ]);
    }

    public function revenue(RevenueAnalyticsService $revenue, CustomerAnalyticsService $customers)
    {
        $data = [
            'kpis' => [
                'total' => $revenue->getTotalRevenue(),
                'mrr' => $revenue->getMRR(),
                'arr' => $revenue->getARR(),
                'arpc' => $revenue->getARPC($customers->getTotalCustomers()),
            ],
            'daily_chart' => $revenue->getDailyRevenueChartData(now()->subDays(30), now()),
            'monthly_chart' => $revenue->getMonthlyRevenueChartData(),
        ];

        return $this->success(new AnalyticsResource($data));
    }

    public function payments(PaymentAnalyticsService $payments)
    {
        $data = [
            'kpis' => [
                'successful' => $payments->getSuccessfulPaymentsCount(),
                'failed' => $payments->getFailedPaymentsCount(),
                'pending' => $payments->getPendingPaymentsCount(),
            ],
            'gateway_chart' => $payments->getGatewayDistributionChartData(),
        ];

        return $this->success(new AnalyticsResource($data));
    }

    public function customers(CustomerAnalyticsService $customers)
    {
        $data = [
            'kpis' => [
                'total' => $customers->getTotalCustomers(),
                'active' => $customers->getActiveCustomers(),
                'new_this_month' => $customers->getNewCustomersThisMonth(),
            ],
            'plan_chart' => $customers->getPlanDistributionChartData(),
        ];

        return $this->success(new AnalyticsResource($data));
    }

    public function reports()
    {
        return $this->success([
            'types' => [
                ['value' => 'revenue', 'label' => 'Revenue Report'],
                ['value' => 'customers', 'label' => 'Customer Report'],
            ],
            'formats' => [
                ['value' => 'csv', 'label' => 'CSV'],
            ],
        ]);
    }

    public function export(Request $request, ReportService $reports)
    {
        $validated = $request->validate([
            'type' => 'required|in:revenue,customers',
            'format' => 'required|in:csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $type = $validated['type'];

        $callback = $type === 'revenue'
            ? $reports->generateRevenueCSV($start, $end)
            : $reports->generateCustomerCSV($start, $end);

        $filename = "{$type}_report_{$start->format('Ymd')}_{$end->format('Ymd')}.csv";

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
