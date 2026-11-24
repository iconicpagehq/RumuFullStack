<?php

namespace App\Services\Analytics;

use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Generate a CSV stream for Revenue Report
     */
    public function generateRevenueCSV(Carbon $startDate, Carbon $endDate)
    {
        $payments = Payment::with(['subscription.user', 'coupon'])
            ->where('payment_status', 'completed')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Payment ID', 'Date', 'Customer Name', 'Customer Email', 'Plan', 'Amount', 'Currency', 'Payment Method', 'Coupon Applied'
        ];

        return $this->streamCsv($headers, $payments->map(function($payment) {
            return [
                $payment->id,
                $payment->created_at->format('Y-m-d H:i:s'),
                $payment->subscription->user->name ?? 'N/A',
                $payment->subscription->user->email ?? 'N/A',
                $payment->subscription->plan_name ?? 'N/A',
                $payment->amount,
                $payment->currency,
                $payment->payment_method,
                $payment->coupon ? $payment->coupon->code : 'None'
            ];
        }));
    }

    /**
     * Generate a CSV stream for Customer Report
     */
    public function generateCustomerCSV(Carbon $startDate, Carbon $endDate)
    {
        $users = User::where('role', 'realtor')
            ->with(['plan', 'subscriptions'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Customer ID', 'Joined Date', 'Name', 'Email', 'Plan', 'Active Subscriptions', 'LTV (Lifetime Value)'
        ];

        return $this->streamCsv($headers, $users->map(function($user) {
            $ltv = Payment::whereHas('subscription', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('payment_status', 'completed')->sum('amount');

            $activeSubs = $user->subscriptions()->where('status', 'active')->count();

            return [
                $user->id,
                $user->created_at->format('Y-m-d H:i:s'),
                $user->name,
                $user->email,
                $user->plan ? $user->plan->name : 'None',
                $activeSubs,
                $ltv
            ];
        }));
    }

    private function streamCsv(array $headers, \Illuminate\Support\Collection $rows)
    {
        $callback = function() use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return $callback;
    }
}
