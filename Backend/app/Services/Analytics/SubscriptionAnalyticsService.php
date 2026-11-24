<?php

namespace App\Services\Analytics;

use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

class SubscriptionAnalyticsService
{
    public function getActiveSubscriptions(): int
    {
        return Cache::remember('analytics_active_subscriptions', 60, function () {
            return Subscription::where('status', 'active')->count();
        });
    }

    public function getExpiredSubscriptions(): int
    {
        return Cache::remember('analytics_expired_subscriptions', 60, function () {
            return Subscription::where('status', 'expired')->count();
        });
    }

    public function getNewSubscriptionsThisMonth(): int
    {
        return Subscription::where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }
}
