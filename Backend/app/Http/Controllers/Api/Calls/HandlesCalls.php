<?php

namespace App\Http\Controllers\Api\Calls;

use App\Http\Resources\CallResource;
use App\Models\CallLog;
use App\Models\CallRecordingAccessLog;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Services\Calls\CallAccessService;
use App\Services\Calls\CallMetricsService;
use App\Services\Calls\CallRecordingService;
use App\Services\Calls\CallService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait HandlesCalls
{
    abstract protected function callScope(Request $request): Builder;

    abstract protected function assertCallAccess(Request $request, CallLog $call): void;

    abstract protected function assertLeadAccess(Request $request, Lead $lead): void;

    abstract protected function callMembership(Request $request): ?EnterpriseMembership;

    abstract protected function canDeleteRecording(Request $request, CallLog $call): bool;

    public function summary(Request $request)
    {
        $payload = app(CallMetricsService::class)->payload($this->callScope($request), 5);
        $payload['recent'] = CallResource::collection($payload['recent']);

        return $this->success($payload);
    }

    public function index(Request $request)
    {
        $query = $this->callScope($request)->withLeadRelation();

        if ($statuses = $this->listFilter($request->input('status'))) {
            $query->whereIn('status', $statuses);
        }

        if ($disposition = $request->input('disposition')) {
            $query->where('disposition', $disposition);
        }

        if ($leadId = $request->input('lead_id')) {
            $query->where('lead_id', $leadId);
        }

        if ($source = $request->input('source')) {
            if (in_array($source, CallLog::SOURCES, true)) {
                $query->where('source', $source);
            }
        }

        if ($agentId = $request->input('agent_id')) {
            $query->where('initiated_by', $agentId);
        }

        if ($request->boolean('with_recording')) {
            $query->where('recording_status', CallLog::RECORDING_AVAILABLE)
                ->whereNull('recording_deleted_at');
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('lead_name', 'like', "%{$search}%")
                    ->orWhere('lead_phone', 'like', "%{$search}%");
            });
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $statusCounts = $this->callScope($request)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $paginator = $query
            ->latest('started_at')
            ->latest('id')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 20))));

        $resource = CallResource::collection($paginator);
        $payload = $resource->response()->getData(true);

        return response()->json([
            'success' => true,
            'data' => $payload['data'] ?? [],
            'meta' => array_merge($payload['meta'] ?? [], [
                'status_counts' => (object) $statusCounts->all(),
            ]),
            'message' => null,
        ]);
    }

    public function initiate(Request $request)
    {
        return $this->error('Calling is available in the Rumu Android app.', 410);
    }

    /**
     * Save a call that already happened on the handset.
     *
     * The app places the call through the device dialer and posts the session
     * afterwards. A retried request with the same `client_call_id` returns the
     * existing call instead of creating a duplicate.
     */
    public function appCall(Request $request)
    {
        $backfillDays = max(1, (int) config('calls.limits.backfill_days', 180));
        $futureLimit = now()->addMinutes(5)->toIso8601String();

        $validated = $request->validate([
            'client_call_id' => 'required|uuid',
            'device_uuid' => 'required|string|max:64',
            'lead_id' => 'required|integer',
            'direction' => 'required|in:outbound',
            'started_at' => [
                'required',
                'date',
                'before_or_equal:' . $futureLimit,
                'after_or_equal:' . now()->subDays($backfillDays)->toIso8601String(),
            ],
            'ended_at' => [
                'required',
                'date',
                'after_or_equal:started_at',
                'before_or_equal:' . $futureLimit,
            ],
            'duration_seconds' => 'required|integer|min:0|max:14400',
            'status' => 'required|in:' . implode(',', [
                CallLog::STATUS_COMPLETED,
                CallLog::STATUS_NO_ANSWER,
                CallLog::STATUS_FAILED,
            ]),
            'disposition' => 'nullable|in:' . implode(',', CallLog::DISPOSITIONS),
            'notes' => 'nullable|string|max:2000',
            'consent_confirmed' => 'required|accepted',
            'client_metadata' => 'nullable|array',
            'client_metadata.app_version' => 'nullable|string|max:32',
            'client_metadata.device_model' => 'nullable|string|max:128',
            'client_metadata.os_version' => 'nullable|string|max:32',
            'client_metadata.recorder_source' => 'nullable|string|max:64',
            'client_metadata.original_filename' => 'nullable|string|max:191',
            'client_metadata.match_confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        $lead = Lead::find($validated['lead_id']);

        if (!$lead) {
            abort(404);
        }

        $this->assertLeadAccess($request, $lead);

        $call = app(CallService::class)->registerAppCall(
            $request->user(),
            $lead,
            $this->callMembership($request),
            $validated,
            (string) $validated['device_uuid']
        );

        $created = $call->wasRecentlyCreated;

        return $this->success(
            new CallResource($call),
            $created ? 'Call saved. The recording will sync separately.' : 'This call was already saved.',
            $created ? 201 : 200
        );
    }

    /**
     * Attach the local handset recording to an app call.
     */
    public function uploadAppRecording(Request $request, CallLog $call)
    {
        $this->assertCallAccess($request, $call);

        $validated = $request->validate([
            'recording' => 'required|file|max:51200',
            'matched_via' => 'nullable|in:' . implode(',', CallLog::MATCHED_VIA),
            'original_filename' => 'nullable|string|max:191',
            'sha256' => 'nullable|string|size:64',
        ]);

        $result = app(CallService::class)->attachAppRecording(
            $request->user(),
            $call,
            $request->file('recording'),
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => new CallResource($result['call']),
            'duplicate_recording' => $result['duplicate'],
            'message' => $result['duplicate']
                ? 'This recording was already uploaded.'
                : 'Recording uploaded.',
        ]);
    }

    public function show(Request $request, CallLog $call)
    {
        $this->assertCallAccess($request, $call);

        return $this->success(new CallResource(
            $call->load(['lead:id,name,phone,campaign_id', 'initiator:id,name,avatar', 'team:id,name'])
        ));
    }

    public function updateDisposition(Request $request, CallLog $call)
    {
        $this->assertCallAccess($request, $call);

        $validated = $request->validate([
            'disposition' => 'required|in:' . implode(',', CallLog::DISPOSITIONS),
            'notes' => 'nullable|string|max:2000',
        ]);

        $call = app(CallService::class)->updateDisposition(
            $call,
            $validated['disposition'],
            $validated['notes'] ?? null
        );

        // The lead is always accessible when the call is visible in the current scope.
        $call->load(['lead:id,name,phone,campaign_id', 'initiator:id,name,avatar', 'team:id,name']);

        return $this->success(new CallResource($call), 'Call outcome saved.');
    }

    public function recording(Request $request, CallLog $call)
    {
        $this->assertCallAccess($request, $call);

        if ($call->recording_status === CallLog::RECORDING_EXPIRED || $call->recording_deleted_at) {
            return $this->error('This recording has expired or was deleted.', 410);
        }

        if (!$call->hasRecordedAudio()) {
            return $this->error(
                $call->recording_status === CallLog::RECORDING_PENDING
                    ? 'The recording is still being prepared. Try again in a minute.'
                    : 'No recording is available for this call.',
                409
            );
        }

        $path = app(CallRecordingService::class)->absolutePath($call);

        if (!$path) {
            return $this->error('The recording file is missing.', 410);
        }

        CallRecordingAccessLog::create([
            'call_log_id' => $call->id,
            'user_id' => $request->user()?->id,
            'action' => $request->boolean('download') ? 'download' : 'stream',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'mp3';
        $filename = 'call-' . $call->uuid . '.' . $extension;

        return response()->file($path, [
            'Content-Type' => $call->recording_mime ?: 'audio/mpeg',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline')
                . '; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteRecording(Request $request, CallLog $call)
    {
        abort_unless($this->canDeleteRecording($request, $call), 403);

        if (!$call->hasRecordedAudio()) {
            return $this->error('No recording is available to delete.', 422);
        }

        app(CallRecordingService::class)->delete($call);

        CallRecordingAccessLog::create([
            'call_log_id' => $call->id,
            'user_id' => $request->user()?->id,
            'action' => 'delete',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return $this->success(null, 'Recording deleted.');
    }

    public function importRecording(Request $request)
    {
        $validated = $request->validate([
            'lead_id' => 'required|integer',
            'recording' => 'required|file|max:51200',
            'recorded_at' => 'required|date|before_or_equal:now',
            'duration_seconds' => 'required|integer|min:1|max:14400',
            'direction' => 'nullable|in:outbound,inbound',
            'disposition' => 'nullable|in:' . implode(',', CallLog::DISPOSITIONS),
            'notes' => 'nullable|string|max:2000',
        ]);

        $lead = Lead::find($validated['lead_id']);

        if (!$lead) {
            abort(404);
        }

        $this->assertLeadAccess($request, $lead);

        $call = app(CallService::class)->importRecording(
            $request->user(),
            $lead,
            $this->callMembership($request),
            $request->file('recording'),
            $validated
        );

        return $this->success(new CallResource($call), 'Recording imported.', 201);
    }

    public function profile(Request $request)
    {
        return $this->success(app(CallService::class)->profile($request->user()));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'agent_number' => 'required|string|max:20',
            'auto_record' => 'nullable|boolean',
            'consent_notice' => 'nullable|boolean',
        ]);

        $profile = app(CallService::class)->updateProfile($request->user(), $validated);

        return $this->success($profile, 'Call settings saved.');
    }

    private function listFilter(mixed $value): array
    {
        if (!$value) {
            return [];
        }

        $values = is_array($value) ? $value : explode(',', (string) $value);
        $values = array_values(array_intersect(array_map('trim', $values), CallLog::STATUSES));

        if (!$values) {
            throw ValidationException::withMessages([
                'status' => ['Select a valid call status.'],
            ]);
        }

        return $values;
    }
}
