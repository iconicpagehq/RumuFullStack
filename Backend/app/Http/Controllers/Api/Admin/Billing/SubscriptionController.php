<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\Analytics\SubscriptionAnalyticsService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends ApiController
{
    public function index(Request $request, SubscriptionAnalyticsService $analytics)
    {
        $request->validate([
            'status' => 'nullable|in:active,expired,cancelled,pending',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Subscription::with('user');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $subscriptions = $query->latest()->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions)->resolve(),
            'meta' => $this->meta($subscriptions),
            'kpis' => [
                'active' => $analytics->getActiveSubscriptions(),
                'expired' => $analytics->getExpiredSubscriptions(),
                'new_this_month' => $analytics->getNewSubscriptionsThisMonth(),
            ],
            'message' => null,
        ]);
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'payments', 'invoices']);

        return $this->success(new SubscriptionResource($subscription));
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

        return $this->success(new SubscriptionResource($subscription->fresh()->load('user')), 'Subscription updated.');
    }

    public function extend(Request $request, Subscription $subscription, SubscriptionService $service)
    {
        $validated = $request->validate([
            'months_to_add' => 'required|integer|min:1|max:60',
        ]);

        $service->extend($subscription, $validated['months_to_add']);

        return $this->success(
            new SubscriptionResource($subscription->fresh()->load('user')),
            "Subscription extended by {$validated['months_to_add']} months."
        );
    }

    public function cancel(Subscription $subscription, SubscriptionService $service)
    {
        $service->cancel($subscription);

        return $this->success(
            new SubscriptionResource($subscription->fresh()->load('user')),
            'Subscription cancelled.'
        );
    }

    private function meta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
