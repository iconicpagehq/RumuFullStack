<?php

namespace App\Services;

use App\Models\PlanUpgradeRequest;
use App\Models\User;
use Carbon\Carbon;

class PlanExpiryService
{
    public const WARNING_DAYS = 7;

    public const DEFAULT_PRO_DAYS = 30;

    public function resolveApprovedExpiry(User $user): ?Carbon
    {
        if (!$user->plan_id) {
            return null;
        }

        $approvedRequest = PlanUpgradeRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('requested_plan_id', $user->plan_id)
            ->latest('approved_at')
            ->first();

        if (!$approvedRequest?->approved_at) {
            return null;
        }

        if ($approvedRequest->expires_at) {
            return $approvedRequest->expires_at->copy();
        }

        $plan = $user->plan;
        if ($plan && $plan->isPro()) {
            return $approvedRequest->approved_at->copy()->addDays(self::DEFAULT_PRO_DAYS);
        }

        return null;
    }

    public function getExpiryInfo(User $user): ?array
    {
        $expiresAt = $this->resolveApprovedExpiry($user);
        if (!$expiresAt || $expiresAt->isPast()) {
            return null;
        }

        $now = now();
        $diff = $now->diff($expiresAt);
        $totalDuration = self::DEFAULT_PRO_DAYS * 24 * 60 * 60;
        $remainingSeconds = $now->diffInSeconds($expiresAt);

        return [
            'expires_at' => $expiresAt,
            'plan_name' => $user->plan?->name ?? 'Plan',
            'days_remaining' => (int) $now->diffInDays($expiresAt),
            'hours_remaining' => $diff->h,
            'minutes_remaining' => $diff->i,
            'seconds_remaining' => $diff->s,
            'expires_formatted' => $expiresAt->format('M d, Y'),
            'total_seconds' => $remainingSeconds,
            'total_duration' => $totalDuration,
            'progress_percent' => round(($remainingSeconds / $totalDuration) * 100, 1),
        ];
    }

    public function getExpiryWarning(User $user): ?array
    {
        $info = $this->getExpiryInfo($user);
        if (!$info) {
            return null;
        }

        if ($info['expires_at']->gt(now()->addDays(self::WARNING_DAYS))) {
            return null;
        }

        return $info;
    }
}
