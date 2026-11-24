<?php

namespace App\Http\Resources;

use App\Models\CallLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CallLog */
class CallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $available = $this->hasRecordedAudio();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'edition' => $this->edition,
            'status' => $this->status,
            'disposition' => $this->disposition,
            'disposition_notes' => $this->disposition_notes,
            'direction' => $this->direction,
            'source' => $this->source,
            'source_label' => $this->sourceLabel(),
            'provider' => $this->provider,
            'client_call_id' => $this->client_call_id,
            'device_uuid' => $this->device_uuid,
            'matched_via' => $this->matched_via,
            'app_metadata' => $this->appMetadata(),
            'client_metadata' => $this->safeClientMetadata(),
            'lead' => [
                'id' => $this->lead_id,
                'name' => $this->lead_name ?: $this->lead?->name,
                'phone' => $this->lead_phone ?: $this->lead?->phone,
            ],
            'agent' => [
                'id' => $this->initiated_by,
                'name' => $this->initiator?->name,
                'avatar' => $this->initiator?->avatar,
            ],
            'team' => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ] : null,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'started_at' => $this->started_at?->toIso8601String(),
            'answered_at' => $this->answered_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => (int) $this->duration_seconds,
            'recording' => [
                'status' => $this->recording_status,
                'available' => $available,
                'pending' => $this->recording_status === CallLog::RECORDING_PENDING,
                'mime' => $this->recording_mime,
                'size' => $this->recording_size,
                'duration_seconds' => $this->recording_duration_seconds,
                'stream_path' => $available
                    ? "/{$this->edition}/calls/{$this->id}/recording"
                    : null,
            ],
            'pending_disposition' => $this->isTerminal() && blank($this->disposition),
            'consent_announced' => (bool) $this->consent_announced,
            'consent_confirmed_at' => $this->consent_confirmed_at?->toIso8601String(),
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Only the app fields that are useful for display are exposed.
     */
    private function appMetadata(): ?array
    {
        return $this->safeClientMetadata();
    }

    /**
     * Whitelisted `client_metadata` keys; raw JSON is never returned as-is.
     */
    private function safeClientMetadata(): ?array
    {
        $meta = $this->client_metadata;

        if (!is_array($meta) || $meta === []) {
            return null;
        }

        $allowed = [
            'app_version',
            'device_model',
            'os_version',
            'recorder_source',
            'original_filename',
            'match_confidence',
            'consent_copy_version',
            'recording_original_filename',
        ];

        $visible = array_intersect_key($meta, array_flip($allowed));

        return $visible === [] ? null : $visible;
    }
}
