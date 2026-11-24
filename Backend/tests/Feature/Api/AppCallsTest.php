<?php

namespace Tests\Feature\Api;

use App\Models\CallLog;
use App\Models\CallRecordingAccessLog;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MobileDevice;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class AppCallsTest extends TestCase
{
    use BuildsEnterpriseWorkspace;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['telephony.enabled' => false]);
    }

    private function makeRealtor(): User
    {
        $plan = Plan::where('key', 'basic')->first() ?? Plan::factory()->basic()->create();

        return User::factory()->create([
            'role' => 'realtor',
            'plan_id' => $plan->id,
        ]);
    }

    private function makeOwnedLead(User $realtor): Lead
    {
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        return Lead::factory()->create([
            'campaign_id' => $campaign->id,
            'phone' => '98765' . random_int(10000, 99999),
        ]);
    }

    private function recordingFile(string $content = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'MIUI_call_9876543210.mp3',
            $content ?? ('ID3' . str_repeat("\x00", 2048))
        );
    }

    private function appCallPayload(Lead $lead, array $overrides = []): array
    {
        return array_merge([
            'client_call_id' => (string) Str::uuid(),
            'device_uuid' => 'device-test-001',
            'lead_id' => $lead->id,
            'direction' => 'outbound',
            'started_at' => now()->subMinutes(8)->toIso8601String(),
            'ended_at' => now()->subMinutes(3)->toIso8601String(),
            'duration_seconds' => 300,
            'status' => 'completed',
            'disposition' => 'interested',
            'notes' => 'Interested in a 3 BHK.',
            'consent_confirmed' => true,
            'client_metadata' => [
                'app_version' => '1.0.0',
                'device_model' => 'Redmi Note 13',
                'os_version' => '14',
                'recorder_source' => 'miui',
                'original_filename' => 'MIUI_call_9876543210.mp3',
                'match_confidence' => 0.92,
            ],
        ], $overrides);
    }

    // ---------------------------------------------------------------- realtor

    public function test_realtor_bootstrap_and_device_registration(): void
    {
        $realtor = $this->makeRealtor();
        Sanctum::actingAs($realtor);

        $this->getJson('/api/v1/mobile/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.edition', 'realtor')
            ->assertJsonPath('data.calls_base', '/realtor/calls')
            ->assertJsonPath('data.calling.mode', 'app_only')
            ->assertJsonPath('data.calling.web_calling', false)
            ->assertJsonPath('data.recording.consent_required', true)
            ->assertJsonPath('data.recording.extensions', ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'amr', 'aac', '3gp', 'webm'])
            ->assertJsonPath('data.device', null);

        $this->postJson('/api/v1/mobile/devices', [
            'device_uuid' => 'device-test-001',
            'model' => 'Redmi Note 13',
            'os_version' => '14',
            'app_version' => '1.0.0',
            'recorder_source' => 'miui',
            'recording_auto_sync' => true,
        ])->assertOk()
            ->assertJsonPath('data.device_uuid', 'device-test-001')
            ->assertJsonPath('data.recorder_source', 'miui');

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $realtor->id,
            'device_uuid' => 'device-test-001',
        ]);

        $this->getJson('/api/v1/mobile/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.device.device_uuid', 'device-test-001');
    }

    public function test_realtor_saves_an_app_call_idempotently_and_syncs_the_recording(): void
    {
        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        Sanctum::actingAs($realtor);

        $payload = $this->appCallPayload($lead);

        $created = $this->postJson('/api/v1/realtor/calls/app', $payload)
            ->assertCreated()
            ->assertJsonPath('data.source', 'app')
            ->assertJsonPath('data.source_label', 'Android app')
            ->assertJsonPath('data.lead.id', $lead->id)
            ->assertJsonPath('data.duration_seconds', 300)
            ->assertJsonPath('data.consent_announced', true)
            ->assertJsonPath('data.recording.status', 'none')
            ->assertJsonPath('data.app_metadata.recorder_source', 'miui');

        $callId = $created->json('data.id');
        $call = CallLog::findOrFail($callId);

        $this->assertSame('app', $call->source);
        $this->assertNotNull($call->consent_confirmed_at);
        $this->assertNotNull($call->uploaded_at);
        $this->assertSame('device-test-001', $call->device_uuid);
        $this->assertSame($payload['client_call_id'], $call->client_call_id);
        $this->assertSame(300, (int) $call->duration_seconds);
        $this->assertNotNull($lead->fresh()->last_contacted_at);

        // Retry with the same client id must not create a second row.
        $this->postJson('/api/v1/realtor/calls/app', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $callId)
            ->assertJsonPath('message', 'This call was already saved.');

        $this->assertSame(1, CallLog::where('client_call_id', $payload['client_call_id'])->count());

        $this->post("/api/v1/realtor/calls/{$callId}/recording", [
            'recording' => $this->recordingFile(),
            'matched_via' => 'auto',
            'original_filename' => 'MIUI_call_9876543210.mp3',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.recording.available', true)
            ->assertJsonPath('duplicate_recording', false)
            ->assertJsonPath('data.matched_via', 'auto');

        $call->refresh();
        $this->assertSame(CallLog::RECORDING_AVAILABLE, $call->recording_status);
        $this->assertSame('auto', $call->matched_via);
        $this->assertTrue($call->hasRecordedAudio());
        Storage::disk('local')->assertExists($call->recording_path);

        $this->get("/api/v1/realtor/calls/{$callId}/recording")
            ->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');

        $this->getJson('/api/v1/realtor/calls?lead_id=' . $lead->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.recording.available', true);

        $this->getJson('/api/v1/realtor/calls?source=app')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Same file again on the same call is a duplicate, not an error.
        $this->post("/api/v1/realtor/calls/{$callId}/recording", [
            'recording' => $this->recordingFile(),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('duplicate_recording', true)
            ->assertJsonPath('data.id', $callId);
    }

    public function test_duplicate_audio_on_a_second_call_points_at_the_existing_call(): void
    {
        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        Sanctum::actingAs($realtor);

        $first = $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead))
            ->assertCreated()
            ->json('data.id');

        $this->post("/api/v1/realtor/calls/{$first}/recording", [
            'recording' => $this->recordingFile(),
        ], ['Accept' => 'application/json'])->assertOk();

        $second = $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead))
            ->assertCreated()
            ->json('data.id');

        $this->post("/api/v1/realtor/calls/{$second}/recording", [
            'recording' => $this->recordingFile(),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('duplicate_recording', true)
            ->assertJsonPath('data.id', $first);

        $this->assertSame(1, CallLog::whereNotNull('recording_sha256')->whereNull('recording_deleted_at')->count());
    }

    public function test_app_call_validation_and_scope_rules(): void
    {
        $realtor = $this->makeRealtor();
        $otherRealtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        $foreignLead = $this->makeOwnedLead($otherRealtor);

        Sanctum::actingAs($realtor);

        $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead, [
            'consent_confirmed' => false,
        ]))->assertStatus(422)->assertJsonValidationErrors('consent_confirmed');

        $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead, [
            'started_at' => now()->addHours(2)->toIso8601String(),
            'ended_at' => now()->addHours(3)->toIso8601String(),
        ]))->assertStatus(422)->assertJsonValidationErrors('started_at');

        $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead, [
            'duration_seconds' => 20000,
        ]))->assertStatus(422)->assertJsonValidationErrors('duration_seconds');

        $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead, [
            'status' => 'in_progress',
        ]))->assertStatus(422)->assertJsonValidationErrors('status');

        $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($foreignLead))
            ->assertNotFound();

        $this->postJson('/api/v1/realtor/calls/initiate', ['lead_id' => $lead->id])
            ->assertStatus(410)
            ->assertJsonPath('success', false);

        // Invalid (non-audio) bytes are rejected with a validation error.
        $callId = $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead))
            ->assertCreated()
            ->json('data.id');

        $this->post("/api/v1/realtor/calls/{$callId}/recording", [
            'recording' => UploadedFile::fake()->createWithContent('notes.mp3', 'not audio at all'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recording');

        Sanctum::actingAs($otherRealtor);
        $this->getJson('/api/v1/realtor/calls')->assertOk()->assertJsonCount(0, 'data');
        $this->post("/api/v1/realtor/calls/{$callId}/recording", [
            'recording' => $this->recordingFile(),
        ], ['Accept' => 'application/json'])->assertNotFound();
    }

    // -------------------------------------------------------------- enterprise

    public function test_enterprise_app_calls_follow_hierarchy_and_only_the_initiator_uploads(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$managerUser, $managerMembership] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $managerUser);
        [$employeeUser, $employeeMembership] = $this->makeMember($enterprise, 'employee', $team);
        $employeeMembership->update(['reports_to_membership_id' => $managerMembership->id]);

        $otherTeam = $this->makeTeam($enterprise, null, 'Outside Team');
        [$outsiderUser] = $this->makeMember($enterprise, 'employee', $otherTeam);

        $lead = $this->makeLead($enterprise, [
            'assigned_to' => $employeeUser->id,
            'team_id' => $team->id,
        ]);

        Sanctum::actingAs($employeeUser);

        $callId = $this->postJson('/api/v1/enterprise/calls/app', $this->appCallPayload($lead))
            ->assertCreated()
            ->assertJsonPath('data.edition', 'enterprise')
            ->json('data.id');

        $this->post("/api/v1/enterprise/calls/{$callId}/recording", [
            'recording' => $this->recordingFile(),
            'matched_via' => 'session',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.recording.available', true);

        // Manager can see the call but cannot upload audio on someone's behalf.
        Sanctum::actingAs($managerUser);
        $this->getJson('/api/v1/enterprise/calls')
            ->assertOk()
            ->assertJsonFragment(['id' => $callId]);
        $this->post("/api/v1/enterprise/calls/{$callId}/recording", [
            'recording' => $this->recordingFile('ID3' . str_repeat("\x01", 2048)),
        ], ['Accept' => 'application/json'])->assertForbidden();

        // Admin can see it and delete the recording under the existing rules.
        Sanctum::actingAs($admin);
        $this->post("/api/v1/enterprise/calls/{$callId}/recording", [
            'recording' => $this->recordingFile('ID3' . str_repeat("\x02", 2048)),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->deleteJson("/api/v1/enterprise/calls/{$callId}/recording")->assertOk();
        $this->getJson("/api/v1/enterprise/calls/{$callId}/recording")->assertStatus(410);

        // Outsider cannot even see the call.
        Sanctum::actingAs($outsiderUser);
        $this->getJson("/api/v1/enterprise/calls/{$callId}")->assertNotFound();
        $this->postJson('/api/v1/enterprise/calls/app', $this->appCallPayload($lead))
            ->assertNotFound();

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/mobile/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.edition', 'enterprise')
            ->assertJsonPath('data.calls_base', '/enterprise/calls')
            ->assertJsonPath('data.enterprise_membership.role', 'admin');
    }

    public function test_mobile_bootstrap_is_not_available_to_platform_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/mobile/bootstrap')->assertForbidden();
        $this->postJson('/api/v1/mobile/devices', ['device_uuid' => 'admin-device'])
            ->assertForbidden();
    }

    public function test_suspended_enterprise_member_gets_no_bootstrap(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$employeeUser, $membership] = $this->makeMember($enterprise, 'employee');
        $membership->update(['status' => 'suspended']);

        Sanctum::actingAs($employeeUser);

        $this->getJson('/api/v1/mobile/bootstrap')->assertForbidden();
    }

    public function test_recording_upload_enforces_size_limit_and_audits_streaming(): void
    {
        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        Sanctum::actingAs($realtor);

        $callId = $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead))
            ->assertCreated()
            ->json('data.id');

        // A lower cap stands in for the 50 MB ceiling so the test stays small.
        config(['calls.recordings.max_bytes' => 1024]);

        $this->post("/api/v1/realtor/calls/{$callId}/recording", [
            'recording' => $this->recordingFile('ID3' . str_repeat("\x00", 4096)),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recording');

        config(['calls.recordings.max_bytes' => 52428800]);

        $this->post("/api/v1/realtor/calls/{$callId}/recording", [
            'recording' => $this->recordingFile(),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->get("/api/v1/realtor/calls/{$callId}/recording")
            ->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');

        $this->assertSame(
            1,
            CallRecordingAccessLog::where('call_log_id', $callId)->where('action', 'stream')->count()
        );
    }

    public function test_app_call_rejects_sessions_older_than_the_backfill_window(): void
    {
        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        Sanctum::actingAs($realtor);

        $this->postJson('/api/v1/realtor/calls/app', $this->appCallPayload($lead, [
            'started_at' => now()->subDays(181)->toIso8601String(),
            'ended_at' => now()->subDays(181)->addMinutes(5)->toIso8601String(),
        ]))->assertStatus(422)->assertJsonValidationErrors('started_at');
    }

    public function test_web_import_still_works_after_the_app_calling_refactor(): void
    {
        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        Sanctum::actingAs($realtor);

        $this->post('/api/v1/realtor/calls/import', [
            'lead_id' => $lead->id,
            'recording' => $this->recordingFile(),
            'recorded_at' => now()->subMinutes(10)->toIso8601String(),
            'duration_seconds' => 90,
            'disposition' => 'connected',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.source', 'import')
            ->assertJsonPath('data.source_label', 'Uploaded recording')
            ->assertJsonPath('data.recording.available', true);
    }
}
