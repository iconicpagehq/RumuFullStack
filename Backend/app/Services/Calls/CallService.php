<?php

namespace App\Services\Calls;

use App\Jobs\DownloadCallRecordingJob;
use App\Models\CallLog;
use App\Models\CallNumber;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\MobileDevice;
use App\Models\User;
use App\Models\UserCallProfile;
use App\Services\Telephony\ProviderCallEvent;
use App\Services\Telephony\TelephonyProvider;
use App\Services\Telephony\TelephonyUnavailableException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CallService
{
    public function __construct(
        private TelephonyProvider $provider,
        private CallRecordingService $recordings,
    ) {
    }

    public function profile(User $user): array
    {
        $profile = UserCallProfile::where('user_id', $user->id)->first();
        $agentNumber = $profile?->agent_number ?: $this->normalizeNumber($user->phone);

        return [
            'agent_number' => $agentNumber,
            'auto_record' => $profile?->auto_record ?? true,
            'consent_notice' => $profile?->consent_notice ?? true,
            'telephony_enabled' => $this->provider->isConfigured(),
            'provider' => $this->provider->name(),
            'retention_days' => $this->recordings->retentionDays(),
            'daily_limit' => (int) config('calls.limits.daily_per_user', 50),
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        $number = $this->normalizeNumber($data['agent_number'] ?? null);

        if (!$number || strlen($number) < 10 || strlen($number) > 15) {
            throw ValidationException::withMessages([
                'agent_number' => ['Enter the mobile number that should ring when you start a call.'],
            ]);
        }

        UserCallProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'agent_number' => $number,
                'auto_record' => (bool) ($data['auto_record'] ?? true),
                'consent_notice' => (bool) ($data['consent_notice'] ?? true),
            ]
        );

        CallNumber::updateOrCreate(
            ['user_id' => $user->id, 'number' => $number],
            [
                'label' => 'Default',
                'provider' => $this->provider->name(),
                'is_verified' => true,
                'verified_at' => now(),
            ]
        );

        return $this->profile($user);
    }

    public function initiate(User $user, Lead $lead, ?EnterpriseMembership $membership = null, string $source = 'app'): CallLog
    {
        if (!$this->provider->isConfigured()) {
            throw new TelephonyUnavailableException(
                'Cloud calling is not configured yet. Add the calling number manually or import the phone recording.'
            );
        }

        if (blank($lead->phone)) {
            throw ValidationException::withMessages([
                'lead_id' => ['This lead does not have a phone number.'],
            ]);
        }

        $agentNumber = $this->userAgentNumber($user);

        if (!$agentNumber) {
            throw ValidationException::withMessages([
                'agent_number' => ['Add your calling number in Call settings before starting a call.'],
            ]);
        }

        $dailyLimit = (int) config('calls.limits.daily_per_user', 50);
        $todayCalls = CallLog::where('initiated_by', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($dailyLimit > 0 && $todayCalls >= $dailyLimit) {
            throw ValidationException::withMessages([
                'lead_id' => ['You have reached the daily calling limit. Try again tomorrow.'],
            ]);
        }

        $active = CallLog::where('initiated_by', $user->id)
            ->whereIn('status', [CallLog::STATUS_INITIATING, CallLog::STATUS_RINGING, CallLog::STATUS_IN_PROGRESS])
            ->latest('id')
            ->first();

        if ($active) {
            throw ValidationException::withMessages([
                'lead_id' => ['Another call is already in progress. Finish it before starting a new one.'],
            ]);
        }

        $profile = UserCallProfile::where('user_id', $user->id)->first();
        $context = $this->editionContext($user, $membership) + [
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'lead_name' => $lead->name,
            'lead_phone' => $lead->phone,
            'provider' => $this->provider->name(),
            'direction' => 'outbound',
            'source' => $source,
            'status' => CallLog::STATUS_INITIATING,
            'from_number' => $agentNumber,
            'to_number' => $lead->phone,
            'started_at' => now(),
            'consent_announced' => $profile?->consent_notice ?? true,
        ];

        $call = CallLog::create($context);

        try {
            $result = $this->provider->initiate($call, $agentNumber);
        } catch (\Throwable $exception) {
            $call->forceFill([
                'status' => CallLog::STATUS_FAILED,
                'ended_at' => now(),
                'provider_payload' => ['error' => $exception->getMessage()],
            ])->save();

            throw $exception;
        }

        $call->forceFill([
            'provider_call_id' => $result->providerCallId,
            'status' => $this->safeStatus($result->status),
            'provider_payload' => $result->payload ?: $call->provider_payload,
        ])->save();

        return $call->fresh()->load(['lead:id,name,phone,campaign_id', 'initiator:id,name,avatar', 'team:id,name']);
    }

    public function applyEvent(CallLog $call, ProviderCallEvent $event): CallLog
    {
        $updates = [];
        $incomingStatus = $this->safeStatus($event->status);

        if (!$call->isTerminal()
            && CallLog::statusRank($incomingStatus) >= CallLog::statusRank($call->status)) {
            $updates['status'] = $incomingStatus;
        }

        if ($event->providerCallId && !$call->provider_call_id) {
            $updates['provider_call_id'] = $event->providerCallId;
        }

        if ($event->startedAt) {
            $updates['started_at'] = $this->parseDate($event->startedAt) ?? $call->started_at;
        }

        if ($event->answeredAt) {
            $updates['answered_at'] = $this->parseDate($event->answeredAt) ?? $call->answered_at;
        } elseif (in_array($updates['status'] ?? $call->status, [CallLog::STATUS_IN_PROGRESS, CallLog::STATUS_COMPLETED], true)
            && !$call->answered_at) {
            $updates['answered_at'] = now();
        }

        if ($event->endedAt) {
            $updates['ended_at'] = $this->parseDate($event->endedAt) ?? $call->ended_at;
        } elseif (in_array($updates['status'] ?? $call->status, CallLog::TERMINAL_STATUSES, true) && !$call->ended_at) {
            $updates['ended_at'] = now();
        }

        if ($event->durationSeconds > 0) {
            $updates['duration_seconds'] = max((int) $call->duration_seconds, $event->durationSeconds);
        }

        $payload = $call->provider_payload ?? [];
        if ($event->recordingUrl) {
            $payload['RecordingUrl'] = $event->recordingUrl;
            $updates['recording_status'] = $call->recording_status === CallLog::RECORDING_AVAILABLE
                ? $call->recording_status
                : CallLog::RECORDING_PENDING;
            $updates['provider_payload'] = $payload;
        }

        $call->forceFill($updates)->save();

        if (in_array($call->status, [CallLog::STATUS_IN_PROGRESS, CallLog::STATUS_COMPLETED], true) && $call->lead_id) {
            Lead::whereKey($call->lead_id)->update(['last_contacted_at' => now()]);
        }

        if ($event->recordingUrl && $call->recording_status === CallLog::RECORDING_PENDING) {
            DownloadCallRecordingJob::dispatch($call->id);
        }

        return $call->fresh();
    }

    public function updateDisposition(CallLog $call, string $disposition, ?string $notes): CallLog
    {
        if (!in_array($disposition, CallLog::DISPOSITIONS, true)) {
            throw ValidationException::withMessages([
                'disposition' => ['Select a valid call outcome.'],
            ]);
        }

        $call->forceFill([
            'disposition' => $disposition,
            'disposition_notes' => $notes,
        ])->save();

        if ($call->lead_id) {
            Lead::whereKey($call->lead_id)->update(['last_contacted_at' => now()]);
        }

        return $call->fresh();
    }

    /**
     * Register a call that already happened on the handset.
     *
     * The Android app starts the call through the device dialer, then posts the
     * session metadata here. Retries are idempotent through `client_call_id`.
     */
    public function registerAppCall(
        User $user,
        Lead $lead,
        ?EnterpriseMembership $membership,
        array $data,
        string $deviceUuid,
    ): CallLog {
        if (blank($lead->phone)) {
            throw ValidationException::withMessages([
                'lead_id' => ['This lead does not have a phone number.'],
            ]);
        }

        $clientCallId = (string) $data['client_call_id'];
        $relations = $this->appCallRelations();

        $existing = $this->findAppCallInScope($clientCallId, $user, $membership);

        if ($existing) {
            return $existing->load($relations);
        }

        $this->assertDailyLimit($user);

        $startedAt = Carbon::parse($data['started_at']);
        $endedAt = isset($data['ended_at']) ? Carbon::parse($data['ended_at']) : null;
        $duration = (int) ($data['duration_seconds'] ?? 0);
        $status = $this->safeAppStatus($data['status'] ?? CallLog::STATUS_COMPLETED);

        $this->assertAppCallWindow($startedAt, $endedAt);

        $context = $this->editionContext($user, $membership) + [
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'lead_name' => $lead->name,
            'lead_phone' => $lead->phone,
            'provider' => null,
            'provider_call_id' => null,
            'direction' => $data['direction'] ?? 'outbound',
            'source' => CallLog::SOURCE_APP,
            'status' => $status,
            'disposition' => $data['disposition'] ?? null,
            'disposition_notes' => $data['notes'] ?? null,
            'from_number' => $this->normalizeNumber($user->phone),
            'to_number' => $lead->phone,
            'started_at' => $startedAt,
            'answered_at' => $status === CallLog::STATUS_COMPLETED ? $startedAt : null,
            'ended_at' => $endedAt,
            'duration_seconds' => max(0, min(14400, $duration)),
            'recording_status' => CallLog::RECORDING_NONE,
            'consent_announced' => true,
            'consent_confirmed_at' => now(),
            'client_call_id' => $clientCallId,
            'device_uuid' => $deviceUuid,
            'uploaded_at' => now(),
            'client_metadata' => $this->appMetadata($data),
        ];

        try {
            $call = CallLog::create($context);
        } catch (QueryException $exception) {
            // Another retry may have inserted the same client_call_id first.
            if (!$this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findAppCallInScope($clientCallId, $user, $membership);

            if (!$existing) {
                throw ValidationException::withMessages([
                    'client_call_id' => ['This call id belongs to another workspace.'],
                ]);
            }

            return $existing->load($relations);
        }

        if ($status === CallLog::STATUS_COMPLETED) {
            $contactedAt = $endedAt ?? $startedAt;

            if ($lead->last_contacted_at === null || $lead->last_contacted_at->lt($contactedAt)) {
                Lead::whereKey($lead->id)->update(['last_contacted_at' => $contactedAt]);
            }
        }

        // `load()` (not `fresh()`) keeps `wasRecentlyCreated` so the controller can answer 201.
        return $call->load($relations);
    }

    /**
     * Attach the handset recording to a call created from the app.
     *
     * Only the person who placed the call may upload its audio. The same file
     * uploaded twice is reported as a duplicate instead of failing, so the app
     * can stop retrying.
     *
     * @return array{call: CallLog, duplicate: bool}
     */
    public function attachAppRecording(
        User $user,
        CallLog $call,
        UploadedFile $file,
        array $meta = [],
    ): array {
        if ((int) $call->initiated_by !== (int) $user->id) {
            abort(403, 'Only the person who placed this call can upload its recording.');
        }

        if ($call->recording_deleted_at) {
            abort(409, 'This recording was deleted. Restore the call before uploading again.');
        }

        $validated = $this->recordings->validateUpload($file);
        $relations = $this->appCallRelations();

        if ($call->hasRecordedAudio()) {
            if (hash_equals((string) $call->recording_sha256, $validated['sha256'])) {
                return ['call' => $call->fresh()->load($relations), 'duplicate' => true];
            }

            abort(409, 'This call already has a recording. Delete it before uploading a replacement.');
        }

        $duplicate = $this->findDuplicateRecording($call, $validated['sha256']);

        if ($duplicate) {
            return ['call' => $duplicate->load($relations), 'duplicate' => true];
        }

        $match = strtolower((string) ($meta['matched_via'] ?? CallLog::MATCHED_VIA_SESSION));

        if (!in_array($match, CallLog::MATCHED_VIA, true)) {
            $match = CallLog::MATCHED_VIA_SESSION;
        }

        $this->recordings->storeUploaded($call, $file, $validated['extension']);

        $metadata = $call->client_metadata ?? [];
        $metadata['original_filename'] = $meta['original_filename'] ?? $file->getClientOriginalName();
        $metadata['matched_via'] = $match;
        $metadata['uploaded_at'] = now()->toIso8601String();

        $call->forceFill([
            'recording_status' => CallLog::RECORDING_AVAILABLE,
            'recording_mime' => $validated['mime'],
            'recording_sha256' => $validated['sha256'],
            'recording_size' => $file->getSize(),
            'matched_via' => $match,
            'uploaded_at' => now(),
            'consent_confirmed_at' => $call->consent_confirmed_at ?? now(),
            'client_metadata' => $metadata,
        ])->save();

        if ($call->device_uuid) {
            MobileDevice::where('user_id', $user->id)
                ->where('device_uuid', $call->device_uuid)
                ->update(['last_seen_at' => now()]);
        }

        return ['call' => $call->fresh()->load($relations), 'duplicate' => false];
    }

    /**
     * @return array<int, string>
     */
    private function appCallRelations(): array
    {
        return ['lead:id,name,phone,campaign_id', 'initiator:id,name,avatar', 'team:id,name'];
    }

    private function findAppCallInScope(
        string $clientCallId,
        User $user,
        ?EnterpriseMembership $membership,
    ): ?CallLog {
        $query = CallLog::query()->where('client_call_id', $clientCallId);

        if ($membership) {
            $query->where('edition', CallLog::EDITION_ENTERPRISE)
                ->where('enterprise_id', $membership->enterprise_id);
        } else {
            $query->where('edition', CallLog::EDITION_REALTOR)
                ->where('realtor_user_id', $user->id);
        }

        return $query->first();
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            || (string) ($exception->errorInfo[0] ?? '') === '23000'
            || (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }

    private function findDuplicateRecording(CallLog $call, string $sha256): ?CallLog
    {
        $query = CallLog::query()
            ->where('recording_sha256', $sha256)
            ->whereNull('recording_deleted_at')
            ->whereKeyNot($call->id);

        if ($call->edition === CallLog::EDITION_ENTERPRISE) {
            $query->where('enterprise_id', $call->enterprise_id);
        } else {
            $query->where('edition', CallLog::EDITION_REALTOR)
                ->where('realtor_user_id', $call->realtor_user_id);
        }

        return $query->first();
    }

    private function assertAppCallWindow(Carbon $startedAt, ?Carbon $endedAt): void
    {
        $backfillDays = max(1, (int) config('calls.limits.backfill_days', 180));

        if ($startedAt->isAfter(now()->addMinutes(5))) {
            throw ValidationException::withMessages([
                'started_at' => ['The call start time cannot be in the future.'],
            ]);
        }

        if ($startedAt->isBefore(now()->subDays($backfillDays))) {
            throw ValidationException::withMessages([
                'started_at' => ['Calls older than ' . $backfillDays . ' days cannot be synced.'],
            ]);
        }

        if ($endedAt && $endedAt->lt($startedAt)) {
            throw ValidationException::withMessages([
                'ended_at' => ['The call end time must be after the start time.'],
            ]);
        }

        if ($endedAt && $endedAt->isAfter(now()->addMinutes(5))) {
            throw ValidationException::withMessages([
                'ended_at' => ['The call end time cannot be in the future.'],
            ]);
        }
    }

    private function assertDailyLimit(User $user): void
    {
        $dailyLimit = (int) config('calls.limits.daily_per_user', 50);

        if ($dailyLimit <= 0) {
            return;
        }

        $todayCalls = CallLog::where('initiated_by', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCalls >= $dailyLimit) {
            throw ValidationException::withMessages([
                'lead_id' => ['You have reached the daily calling limit. Try again tomorrow.'],
            ]);
        }
    }

    private function safeAppStatus(string $status): string
    {
        return in_array($status, [
            CallLog::STATUS_COMPLETED,
            CallLog::STATUS_NO_ANSWER,
            CallLog::STATUS_FAILED,
        ], true) ? $status : CallLog::STATUS_COMPLETED;
    }

    private function appMetadata(array $data): array
    {
        $meta = is_array($data['client_metadata'] ?? null) ? $data['client_metadata'] : [];

        return array_filter([
            'app_version' => $meta['app_version'] ?? null,
            'device_model' => $meta['device_model'] ?? null,
            'os_version' => $meta['os_version'] ?? null,
            'recorder_source' => $meta['recorder_source'] ?? null,
            'original_filename' => $meta['original_filename'] ?? null,
            'match_confidence' => isset($meta['match_confidence']) ? (float) $meta['match_confidence'] : null,
            'consent_copy_version' => config('calls.consent.copy_version'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function importRecording(
        User $user,
        Lead $lead,
        ?EnterpriseMembership $membership,
        UploadedFile $file,
        array $data,
    ): CallLog {
        $validated = $this->recordings->validateUpload($file);

        $duplicateQuery = CallLog::query()
            ->where('recording_sha256', $validated['sha256'])
            ->whereNull('recording_deleted_at');

        if ($membership) {
            $duplicateQuery->where('enterprise_id', $membership->enterprise_id);
        } else {
            $duplicateQuery->where('edition', CallLog::EDITION_REALTOR)->where('realtor_user_id', $user->id);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'recording' => ['This recording has already been imported.'],
            ]);
        }

        $recordedAt = Carbon::parse($data['recorded_at']);
        $duration = (int) $data['duration_seconds'];

        $call = DB::transaction(function () use ($user, $lead, $membership, $file, $data, $validated, $recordedAt, $duration) {
            $context = $this->editionContext($user, $membership) + [
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'lead_name' => $lead->name,
                'lead_phone' => $lead->phone,
                'provider' => null,
                'direction' => $data['direction'] ?? 'outbound',
                'source' => 'import',
                'status' => CallLog::STATUS_COMPLETED,
                'disposition' => $data['disposition'] ?? null,
                'disposition_notes' => $data['notes'] ?? null,
                'from_number' => $this->userAgentNumber($user),
                'to_number' => $lead->phone,
                'started_at' => $recordedAt->copy()->subSeconds($duration),
                'answered_at' => $recordedAt->copy(),
                'ended_at' => $recordedAt->copy(),
                'duration_seconds' => $duration,
                'recording_status' => CallLog::RECORDING_PENDING,
                'recording_mime' => $validated['mime'],
                'recording_sha256' => $validated['sha256'],
                'consent_announced' => true,
            ];

            $call = CallLog::create($context);

            $this->recordings->storeUploaded($call, $file, $validated['extension']);

            if ($lead->last_contacted_at === null || $lead->last_contacted_at->lt($recordedAt)) {
                Lead::whereKey($lead->id)->update(['last_contacted_at' => $recordedAt]);
            }

            return $call->fresh();
        });

        return $call->load(['lead:id,name,phone,campaign_id', 'initiator:id,name,avatar', 'team:id,name']);
    }

    public function userAgentNumber(User $user): ?string
    {
        $profile = UserCallProfile::where('user_id', $user->id)->first();
        $number = $this->normalizeNumber($profile?->agent_number ?: $user->phone);

        return $number && strlen($number) >= 10 ? $number : null;
    }

    private function editionContext(User $user, ?EnterpriseMembership $membership): array
    {
        if (!$membership) {
            return [
                'edition' => CallLog::EDITION_REALTOR,
                'realtor_user_id' => $user->id,
                'initiated_by' => $user->id,
            ];
        }

        $membership->loadMissing('primaryTeamMembership');

        return [
            'edition' => CallLog::EDITION_ENTERPRISE,
            'enterprise_id' => $membership->enterprise_id,
            'enterprise_membership_id' => $membership->id,
            'team_id' => $membership->primaryTeamMembership?->team_id,
            'initiated_by' => $user->id,
        ];
    }

    private function normalizeNumber(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (!$digits) {
            return null;
        }

        if (strlen($digits) === 10) {
            return $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        return strlen($digits) <= 15 ? $digits : substr($digits, -15);
    }

    private function safeStatus(string $status): string
    {
        return in_array($status, CallLog::STATUSES, true) ? $status : CallLog::STATUS_RINGING;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
