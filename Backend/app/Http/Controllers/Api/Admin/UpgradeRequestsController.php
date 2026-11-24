<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Services\EnterpriseAuditService;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpgradeRequestsController extends ApiController
{
    public function index()
    {
        $requests = PlanUpgradeRequest::with(['user', 'currentPlan', 'requestedPlan', 'approvedBy'])
            ->latest()
            ->paginate(20);

        $requests->through(function (PlanUpgradeRequest $upgradeRequest) {
            $membership = $upgradeRequest->user?->enterpriseMembership;
            $upgradeRequest->setAttribute('enterprise', $membership?->enterprise ? [
                'id' => $membership->enterprise->id,
                'name' => $membership->enterprise->name,
            ] : null);
            $upgradeRequest->setAttribute('request_type', 'plan');

            return $upgradeRequest;
        });

        return $this->paginated($requests);
    }

    public function approve(
        Request $request,
        PlanUpgradeRequest $upgradeRequest,
        PlatformAuditService $platformAudit,
        EnterpriseAuditService $enterpriseAudit,
    ) {
        if ($upgradeRequest->status !== 'pending') {
            return $this->error('This request has already been processed.', 409);
        }

        $user = $upgradeRequest->user;
        $requestedPlan = $upgradeRequest->requestedPlan;
        $membership = $user?->role === 'enterprise' ? $user->activeEnterpriseMembership() : null;

        if ($user?->role === 'enterprise' && !$membership) {
            return $this->error('This enterprise account is no longer active.', 409);
        }

        if ($membership && !$requestedPlan?->supportsEnterprise()) {
            return $this->error('The selected plan is not available for enterprise accounts.', 422);
        }

        DB::transaction(function () use ($request, $upgradeRequest, $user, $requestedPlan, $membership, $platformAudit, $enterpriseAudit) {
            $user->plan_id = $upgradeRequest->requested_plan_id;
            $user->save();

            if ($membership) {
                $enterprise = $membership->enterprise;
                $billingInterval = $enterprise->settings['billing_interval'] ?? 'monthly';
                $startsAt = now();
                $expiresAt = $billingInterval === 'annual' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth();

                $enterprise->update([
                    'plan_id' => $requestedPlan->id,
                    'trial_ends_at' => null,
                ]);

                $subscription = Subscription::where('user_id', $user->id)->latest()->first();
                $payload = [
                    'plan_name' => $requestedPlan->name,
                    'duration_months' => $billingInterval === 'annual' ? 12 : 1,
                    'start_date' => $startsAt,
                    'expiry_date' => $expiresAt,
                    'status' => 'active',
                    'price' => $requestedPlan->price,
                    'internal_notes' => "Enterprise plan activated for {$enterprise->name} (enterprise #{$enterprise->id}).",
                ];

                $subscription ? $subscription->update($payload) : Subscription::create(['user_id' => $user->id, ...$payload]);
            }

            $upgradeRequest->update([
                'status' => 'approved',
                'admin_notes' => $request->input('notes'),
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'expires_at' => $requestedPlan?->isPro() ? now()->addDays(30) : null,
            ]);

            $platformAudit->log($request->user(), 'upgrade_request.approved', $upgradeRequest, 'Plan upgrade approved', [
                'user_id' => $user->id,
                'plan_id' => $requestedPlan?->id,
            ]);

            if ($membership) {
                $enterpriseAudit->log($membership->enterprise, $request->user(), 'billing.upgrade_approved', $upgradeRequest, 'Plan upgrade approved', [
                    'plan_id' => $requestedPlan?->id,
                ]);
            }
        });

        return $this->success(null, 'Upgrade request approved.');
    }

    public function reject(
        Request $request,
        PlanUpgradeRequest $upgradeRequest,
        PlatformAuditService $platformAudit,
    ) {
        if ($upgradeRequest->status !== 'pending') {
            return $this->error('This request has already been processed.', 409);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $upgradeRequest->update([
            'status' => 'rejected',
            'admin_notes' => $validated['reason'],
            'approved_by' => $request->user()->id,
        ]);

        $platformAudit->log($request->user(), 'upgrade_request.rejected', $upgradeRequest, 'Plan upgrade rejected', [
            'reason' => $validated['reason'],
        ]);

        return $this->success(null, 'Upgrade request rejected.');
    }
}

