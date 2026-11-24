<?php

namespace App\Services\Analytics;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class RevenueAnalyticsService
{
    /**
     * Total Revenue (All time successful payments)
     */
    public function getTotalRevenue(): float
    {
        return Cache::remember('analytics_total_revenue', 60, function () {
            return (float) Payment::where('payment_status', 'completed')->sum('amount');
        });
    }

    /**
     * Monthly Recurring Revenue (MRR) - Approximated as revenue in the last 30 days
     */
    public function getMRR(): float
    {
        return Cache::remember('analytics_mrr', 60, function () {
            return (float) Payment::where('payment_status', 'completed')
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount');
        });
    }

    /**
     * Annual Recurring Revenue (ARR) - Approximated as MRR * 12
     */
    public function getARR(): float
    {
        return $this->getMRR() * 12;
    }

    /**
     * Average Revenue Per Customer (ARPC)
     */
    public function getARPC(int $totalCustomers): float
    {
        if ($totalCustomers === 0) return 0.0;
        return round($this->getTotalRevenue() / $totalCustomers, 2);
    }

    /**
     * Daily Revenue for charting (Last 30 days)
     */
    public function getDailyRevenueChartData(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('payment_status', 'completed')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('M d');
            $data[] = isset($payments[$dateStr]) ? (float) $payments[$dateStr]->total : 0;
            $currentDate->addDay();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Monthly Revenue for charting (Last 12 months)
     */
    public function getMonthlyRevenueChartData(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        $driver = Payment::query()->getConnection()->getDriverName();

        $select = match ($driver) {
            'sqlite' => "strftime('%Y', created_at) as year, strftime('%m', created_at) as month, SUM(amount) as total",
            'pgsql' => 'EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, SUM(amount) as total',
            default => 'YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total',
        };

        $payments = Payment::selectRaw($select)
            ->where('payment_status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $paymentsDict = [];
        foreach ($payments as $p) {
            $paymentsDict[$p->year . '-' . str_pad($p->month, 2, '0', STR_PAD_LEFT)] = $p->total;
        }

        $labels = [];
        $data = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $key = $currentDate->format('Y-m');
            $labels[] = $currentDate->format('M Y');
            $data[] = isset($paymentsDict[$key]) ? (float) $paymentsDict[$key] : 0;
            $currentDate->addMonth();
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
