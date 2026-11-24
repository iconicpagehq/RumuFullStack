<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Services\PlanExpiryService;
use Illuminate\Http\Request;

class PlanController extends ApiController
{
    public function index(Request $request, PlanExpiryService $planExpiry)
    {
        $user = $request->user();
        $plans = Plan::active()->ordered()->get();
        $currentPlan = $user->plan;
        $pendingRequest = PlanUpgradeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('requestedPlan')
            ->first();

        return $this->success([
            'plans' => PlanResource::collection($plans),
            'current_plan' => $currentPlan ? new PlanResource($currentPlan) : null,
            'campaigns_count' => $user->campaigns()->count(),
            'pending_upgrade' => $pendingRequest ? [
                'id' => $pendingRequest->id,
                'requested_plan' => new PlanResource($pendingRequest->requestedPlan),
                'created_at' => $pendingRequest->created_at?->toIso8601String(),
            ] : null,
            'expiry_info' => $planExpiry->getExpiryInfo($user),
        ]);
    }

    public function requestUpgrade(Request $request)
    {
        $user = $request->user();

        $existingRequest = PlanUpgradeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return $this->error('You already have a pending upgrade request.', 409);
        }

        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $targetPlan = Plan::active()->find($validated['plan_id']);

        if (!$targetPlan) {
            return $this->error('That plan is not available.', 422);
        }

        $currentPlan = $user->plan;

        if (!$targetPlan->isUpgradeFrom($currentPlan)) {
            return $this->error('You cannot upgrade to that plan from your current plan.', 422);
        }

        PlanUpgradeRequest::create([
            'user_id' => $user->id,
            'current_plan_id' => $user->plan_id ?? Plan::where('key', 'basic')->first()?->id,
            'requested_plan_id' => $targetPlan->id,
            'status' => 'pending',
        ]);

        return $this->success(null, "Upgrade request for {$targetPlan->name} sent to admin.", 201);
    }
}
