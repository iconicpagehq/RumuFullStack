<?php

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class CustomerAnalyticsService
{
    public function getTotalCustomers(): int
    {
        return Cache::remember('analytics_total_customers', 60, function () {
            // Assuming role=realtor is the customer
            return User::where('role', 'realtor')->count();
        });
    }

    public function getActiveCustomers(): int
    {
        return Cache::remember('analytics_active_customers', 60, function () {
            return User::where('role', 'realtor')->whereHas('subscriptions', function ($query) {
                $query->where('status', 'active');
            })->count();
        });
    }

    public function getNewCustomersThisMonth(): int
    {
        return User::where('role', 'realtor')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Plan distribution for Doughnut chart
     */
    public function getPlanDistributionChartData(): array
    {
        $users = User::where('role', 'realtor')
            ->whereNotNull('plan_id')
            ->with('plan')
            ->get()
            ->groupBy('plan.name');

        $labels = [];
        $data = [];

        foreach ($users as $planName => $group) {
            if ($planName) {
                $labels[] = ucfirst($planName);
                $data[] = $group->count();
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
