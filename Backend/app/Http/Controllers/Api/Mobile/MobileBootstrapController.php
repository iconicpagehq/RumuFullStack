<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\MobileDevice;
use App\Services\Calls\CallRecordingService;
use Illuminate\Http\Request;

class MobileBootstrapController extends ApiController
{
    /**
     * Everything the Android app needs before its first call.
     *
     * Kept small on purpose: edition + calls base path + consent/recording rules.
     * No secrets, no provider details, no other workspace data.
     */
    public function show(Request $request, CallRecordingService $recordings)
    {
        $user = $request->user();

        if (!in_array($user->role, ['realtor', 'enterprise'], true)) {
            return $this->error('The Rumu app is available for realtor and enterprise accounts.', 403);
        }

        $membership = $user->role === 'enterprise'
            ? $user->activeEnterpriseMembership()
            : null;

        if ($user->role === 'enterprise' && !$membership) {
            return $this->error('No active enterprise workspace is linked to this account.', 403);
        }

        $edition = $user->role === 'enterprise' ? 'enterprise' : 'realtor';
        $backfillDays = max(1, (int) config('calls.limits.backfill_days', 180));

        $device = MobileDevice::where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->first();

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
            ],
            'edition' => $edition,
            'calls_base' => "/{$edition}/calls",
            'enterprise_membership' => $membership ? [
                'id' => $membership->id,
                'enterprise_id' => $membership->enterprise_id,
                'role' => $membership->role,
                'team_id' => $membership->primaryTeamMembership?->team_id,
            ] : null,
            'calling' => [
                'mode' => 'app_only',
                'web_calling' => false,
                'dialer' => 'ACTION_DIAL',
            ],
            'recording' => [
                'max_bytes' => $recordings->maxBytes(),
                'extensions' => config('calls.recordings.extensions', []),
                'consent_required' => (bool) config('calls.consent.required', true),
                'consent_copy_version' => (string) config('calls.consent.copy_version', '2026-11-01'),
                'retention_days' => $recordings->retentionDays(),
                'daily_limit' => (int) config('calls.limits.daily_per_user', 50),
                'backfill_days' => $backfillDays,
            ],
            'device' => $device ? [
                'device_uuid' => $device->device_uuid,
                'model' => $device->model,
                'recorder_source' => $device->recorder_source,
                'recording_auto_sync' => $device->recording_auto_sync,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ] : null,
            'app' => [
                'min_version' => (string) config('calls.app.min_version', '1.0.0'),
            ],
        ]);
    }
}
