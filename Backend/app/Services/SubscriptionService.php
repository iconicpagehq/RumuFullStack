<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Prevent overlapping active subscriptions for the same user.
     * Returns true if there is an overlap.
     */
    public function hasOverlap(User $user, $startDate, $expiryDate, ?Subscription $excludeSubscription = null): bool
    {
        $query = $user->subscriptions()
            ->whereIn('status', ['active'])
            ->where(function ($q) use ($startDate, $expiryDate) {
                $q->where(function ($q1) use ($startDate, $expiryDate) {
                    $q1->where('start_date', '<=', $expiryDate)
                       ->where('expiry_date', '>=', $startDate);
                })
                ->orWhereNull('expiry_date'); // Ongoing subscriptions overlap with everything after start_date
            });

        if ($excludeSubscription) {
            $query->where('id', '!=', $excludeSubscription->id);
        }

        return $query->exists();
    }

    /**
     * Administratively extend a subscription by a number of months.
     */
    public function extend(Subscription $subscription, int $monthsToAdd): Subscription
    {
        if ($subscription->expiry_date) {
            $subscription->expiry_date = Carbon::parse($subscription->expiry_date)->addMonths($monthsToAdd);
        }
        
        $subscription->save();
        return $subscription;
    }

    /**
     * Cancel an active subscription.
     */
    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            // Note: We retain the expiry_date so the customer maintains access until the end of their cycle.
        ]);

        return $subscription;
    }
}
