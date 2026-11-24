<?php

namespace App\Services\Analytics;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class PaymentAnalyticsService
{
    public function getSuccessfulPaymentsCount(): int
    {
        return Cache::remember('analytics_successful_payments', 60, function () {
            return Payment::where('payment_status', 'completed')->count();
        });
    }

    public function getFailedPaymentsCount(): int
    {
        return Cache::remember('analytics_failed_payments', 60, function () {
            return Payment::where('payment_status', 'failed')->count();
        });
    }

    public function getPendingPaymentsCount(): int
    {
        return Cache::remember('analytics_pending_payments', 60, function () {
            return Payment::where('payment_status', 'pending')->count();
        });
    }

    public function getGatewayDistributionChartData(): array
    {
        // Currently everything is Razorpay but this aggregates for future-proofing
        $gateways = Payment::where('payment_status', 'completed')
            ->selectRaw('payment_method, count(*) as count')
            ->groupBy('payment_method')
            ->get();

        $labels = [];
        $data = [];
        
        foreach ($gateways as $g) {
            $labels[] = ucfirst($g->payment_method ?: 'Unknown');
            $data[] = $g->count;
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
