<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\SubscriptionAnalyticsService;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(SubscriptionAnalyticsService $subscriptionSvc)
    {
        $kpis = [
            'active' => $subscriptionSvc->getActiveSubscriptions(),
            'expired' => $subscriptionSvc->getExpiredSubscriptions(),
            'new_this_month' => $subscriptionSvc->getNewSubscriptionsThisMonth(),
        ];

        $subscriptions = Subscription::with('user')->orderBy('created_at', 'desc')->get();

        return view('admin.analytics.subscriptions', compact('kpis', 'subscriptions'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,expired,cancelled,pending',
            'expiry_date' => 'nullable|date',
            'internal_notes' => 'nullable|string|max:1000',
        ]);

        $subscription->update([
            'status' => $validated['status'],
            'expiry_date' => $validated['expiry_date'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        return back()->with('success', 'Subscription updated successfully.');
    }
}
