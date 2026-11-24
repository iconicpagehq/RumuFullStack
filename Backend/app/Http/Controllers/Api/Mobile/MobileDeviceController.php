<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\MobileDevice;
use Illuminate\Http\Request;

class MobileDeviceController extends ApiController
{
    /**
     * Register or refresh the handset that is syncing calls.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['realtor', 'enterprise'], true)) {
            return $this->error('The Rumu app is available for realtor and enterprise accounts.', 403);
        }

        $validated = $request->validate([
            'device_uuid' => 'required|string|max:64',
            'platform' => 'nullable|in:android',
            'model' => 'nullable|string|max:128',
            'os_version' => 'nullable|string|max:32',
            'app_version' => 'nullable|string|max:32',
            'recorder_source' => 'nullable|string|max:64',
            'recording_auto_sync' => 'nullable|boolean',
        ]);

        $device = MobileDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_uuid' => $validated['device_uuid'],
            ],
            [
                'platform' => $validated['platform'] ?? MobileDevice::PLATFORM_ANDROID,
                'model' => $validated['model'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'recorder_source' => $validated['recorder_source'] ?? null,
                'recording_auto_sync' => $validated['recording_auto_sync'] ?? true,
                'last_seen_at' => now(),
            ]
        );

        return $this->success([
            'device_uuid' => $device->device_uuid,
            'platform' => $device->platform,
            'model' => $device->model,
            'recorder_source' => $device->recorder_source,
            'recording_auto_sync' => $device->recording_auto_sync,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
        ], 'Device registered.');
    }
}
