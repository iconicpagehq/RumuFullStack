<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingAnalyticsService
{
    /**
     * Get all high-level KPIs.
     */
    public function getKpis(): array
    {
        return [
            'total_revenue' => Payment::where('payment_status', 'completed')->sum('amount'),
            'monthly_revenue' => Payment::where('payment_status', 'completed')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'expired_subscriptions' => Subscription::where('status', 'expired')->count(),
            'invoices_sent' => Invoice::whereNotNull('emailed_at')->count(),
            'pending_payments' => Payment::where('payment_status', 'pending')->count(),
        ];
    }

    /**
     * Get monthly revenue for the last 6 months.
     */
    public function getMonthlyRevenueChart(): array
    {
        $months = collect();
        $revenue = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $total = Payment::where('payment_status', 'completed')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');
            
            $revenue->push($total);
        }

        return [
            'labels' => $months->toArray(),
            'data' => $revenue->toArray()
        ];
    }

    /**
     * Get subscription growth (new active subs) for the last 6 months.
     */
    public function getSubscriptionGrowthChart(): array
    {
        $months = collect();
        $growth = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $count = Subscription::where('status', 'active')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $growth->push($count);
        }

        return [
            'labels' => $months->toArray(),
            'data' => $growth->toArray()
        ];
    }

    /**
     * Get distribution of active subscriptions by plan.
     */
    public function getPlanDistributionChart(): array
    {
        $distribution = Subscription::where('status', 'active')
            ->select('plan_name', DB::raw('count(*) as total'))
            ->groupBy('plan_name')
            ->get();

        return [
            'labels' => $distribution->pluck('plan_name')->toArray(),
            'data' => $distribution->pluck('total')->toArray()
        ];
    }

    /**
     * Get recently completed payments.
     */
    public function getRecentPayments(int $limit = 5)
    {
        return Payment::with('subscription.user')
            ->where('payment_status', 'completed')
            ->latest('payment_date')
            ->take($limit)
            ->get();
    }

    /**
     * Get recent invoices.
     */
    public function getRecentInvoices(int $limit = 5)
    {
        return Invoice::with(['user', 'subscription'])
            ->latest()
            ->take($limit)
            ->get();
    }
}
