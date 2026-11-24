<?php

namespace Tests\Feature\Api;

use App\Jobs\DownloadCallRecordingJob;
use App\Models\CallLog;
use App\Models\CallRecordingAccessLog;
use App\Models\CallWebhookEvent;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class CallsApiTest extends TestCase
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

    private function makeOwnedLead(User $realtor, array $overrides = []): Lead
    {
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        return Lead::factory()->create(array_merge([
            'campaign_id' => $campaign->id,
            'phone' => '98765' . random_int(10000, 99999),
        ], $overrides));
    }

    private function makeCall(array $overrides = []): CallLog
    {
        return CallLog::create(array_merge([
            'edition' => CallLog::EDITION_REALTOR,
            'lead_name' => 'Call Lead',
            'lead_phone' => '9876500000',
            'status' => CallLog::STATUS_COMPLETED,
            'recording_status' => CallLog::RECORDING_NONE,
            'started_at' => now(),
            'duration_seconds' => 60,
        ], $overrides));
    }

    private function recordingFile(?string $content = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'call.mp3',
            $content ?? ('ID3' . str_repeat("\x00", 2048))
        );
    }

    public function test_realtor_can_import_and_stream_a_scoped_recording(): void
    {
        $realtor = $this->makeRealtor();
        $otherRealtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor, ['name' => 'Own Call Lead']);

        Sanctum::actingAs($realtor);

        $this->getJson('/api/v1/realtor/calls/profile')
            ->assertOk()
            ->assertJsonPath('data.telephony_enabled', false);

        $this->putJson('/api/v1/realtor/calls/profile', ['agent_number' => '9876543210'])
            ->assertOk()
            ->assertJsonPath('data.agent_number', '9876543210');

        $this->postJson('/api/v1/realtor/calls/initiate', ['lead_id' => $lead->id])
            ->assertStatus(410)
            ->assertJsonPath('success', false);

        $response = $this->post('/api/v1/realtor/calls/import', [
            'lead_id' => $lead->id,
            'recording' => $this->recordingFile(),
            'recorded_at' => now()->subMinutes(5)->toIso8601String(),
            'duration_seconds' => 120,
            'disposition' => 'interested',
            'notes' => 'Asked for a site visit.',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.recording.available', true)
            ->assertJsonPath('data.source', 'import');

        $callId = $response->json('data.id');
        $call = CallLog::findOrFail($callId);

        Storage::disk('local')->assertExists($call->recording_path);

        $this->getJson('/api/v1/realtor/calls?lead_id=' . $lead->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $callId);

        $this->getJson('/api/v1/realtor/calls/summary')
            ->assertOk()
            ->assertJsonPath('data.today.total', 1)
            ->assertJsonPath('data.today.recorded', 1);

        $this->getJson('/api/v1/realtor/dashboard')
            ->assertOk()
            ->assertJsonPath('data.calls.today.total', 1);

        $this->get("/api/v1/realtor/calls/{$callId}/recording")
            ->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');

        $this->assertSame(1, CallRecordingAccessLog::where('call_log_id', $callId)->where('action', 'stream')->count());

        $this->patchJson("/api/v1/realtor/calls/{$callId}/disposition", [
            'disposition' => 'call_back',
            'notes' => 'Call after 5pm.',
        ])->assertOk()
            ->assertJsonPath('data.disposition', 'call_back');

        $this->post('/api/v1/realtor/calls/import', [
            'lead_id' => $lead->id,
            'recording' => $this->recordingFile(),
            'recorded_at' => now()->toIso8601String(),
            'duration_seconds' => 60,
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recording');

        Sanctum::actingAs($otherRealtor);

        $this->getJson('/api/v1/realtor/calls')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/realtor/calls/{$callId}")->assertNotFound();
        $this->get("/api/v1/realtor/calls/{$callId}/recording")->assertNotFound();

        Sanctum::actingAs($realtor);

        $this->deleteJson("/api/v1/realtor/calls/{$callId}/recording")
            ->assertOk();

        $this->get("/api/v1/realtor/calls/{$callId}/recording")->assertStatus(410);
    }

    public function test_telephony_webhook_verifies_signature_and_is_idempotent(): void
    {
        config([
            'telephony.enabled' => true,
            'telephony.provider' => 'exotel',
            'telephony.webhook.secret' => 'test-webhook-secret',
            'telephony.exotel' => [
                'subdomain' => 'test.exotel.com',
                'account_sid' => 'acct-1',
                'api_key' => 'api-key',
                'api_token' => 'api-token',
                'caller_id' => '08012345678',
            ],
        ]);

        Queue::fake();

        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor);
        $call = $this->makeCall([
            'realtor_user_id' => $realtor->id,
            'initiated_by' => $realtor->id,
            'lead_id' => $lead->id,
            'status' => CallLog::STATUS_RINGING,
            'duration_seconds' => 0,
            'provider' => 'exotel',
            'provider_call_id' => 'provider-call-1',
        ]);

        $body = json_encode([
            'Call' => [
                'Sid' => 'provider-call-1',
                'Status' => 'completed',
                'Duration' => 42,
                'RecordingUrl' => 'https://provider.test/recordings/call-1.mp3',
                'CustomField' => $call->uuid,
            ],
        ]);

        $signature = hash_hmac('sha256', $body, 'test-webhook-secret');

        $this->call('POST', '/api/v1/webhooks/telephony/exotel', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_RUMU_SIGNATURE' => $signature,
        ], $body)->assertOk()->assertJsonPath('data.matched', true);

        $call->refresh();
        $this->assertSame(CallLog::STATUS_COMPLETED, $call->status);
        $this->assertSame(42, $call->duration_seconds);
        $this->assertSame(CallLog::RECORDING_PENDING, $call->recording_status);
        $this->assertSame(1, CallWebhookEvent::count());

        Queue::assertPushed(DownloadCallRecordingJob::class, fn ($job) => $job->callId === $call->id);

        $this->call('POST', '/api/v1/webhooks/telephony/exotel', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_RUMU_SIGNATURE' => $signature,
        ], $body)->assertOk()->assertJsonPath('data.duplicate', true);

        $this->assertSame(1, CallWebhookEvent::count());

        $this->call('POST', '/api/v1/webhooks/telephony/exotel', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_RUMU_SIGNATURE' => 'invalid',
        ], $body)->assertStatus(403);
    }

    public function test_enterprise_call_visibility_follows_the_reporting_hierarchy(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$managerUser, $managerMembership] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $managerUser);
        [$employeeUser, $employeeMembership] = $this->makeMember($enterprise, 'employee', $team);
        $employeeMembership->update(['reports_to_membership_id' => $managerMembership->id]);

        $otherTeam = $this->makeTeam($enterprise, null, 'Outside Team');
        [$outsiderUser, $outsiderMembership] = $this->makeMember($enterprise, 'employee', $otherTeam);

        $employeeCall = $this->makeCall([
            'edition' => CallLog::EDITION_ENTERPRISE,
            'enterprise_id' => $enterprise->id,
            'enterprise_membership_id' => $employeeMembership->id,
            'team_id' => $team->id,
            'initiated_by' => $employeeUser->id,
        ]);

        $outsiderCall = $this->makeCall([
            'edition' => CallLog::EDITION_ENTERPRISE,
            'enterprise_id' => $enterprise->id,
            'enterprise_membership_id' => $outsiderMembership->id,
            'team_id' => $otherTeam->id,
            'initiated_by' => $outsiderUser->id,
        ]);

        Sanctum::actingAs($employeeUser);

        $this->getJson('/api/v1/enterprise/calls')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $employeeCall->id);

        $this->getJson("/api/v1/enterprise/calls/{$outsiderCall->id}")->assertNotFound();

        Sanctum::actingAs($managerUser);

        $this->getJson('/api/v1/enterprise/calls')
            ->assertOk()
            ->assertJsonFragment(['id' => $employeeCall->id])
            ->assertJsonMissing(['id' => $outsiderCall->id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/enterprise/calls')
            ->assertOk()
            ->assertJsonFragment(['id' => $employeeCall->id])
            ->assertJsonFragment(['id' => $outsiderCall->id]);

        $this->getJson('/api/v1/enterprise/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['calls' => ['today', 'by_status', 'recent']]]);
    }

    public function test_enterprise_import_requires_a_visible_lead(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [$employeeUser, $employeeMembership] = $this->makeMember($enterprise, 'employee');
        $team = $this->makeTeam($enterprise, $admin);
        $employeeMembership->update([
            'reports_to_membership_id' => $adminMembership->id,
        ]);

        $ownLead = $this->makeLead($enterprise, [
            'assigned_to' => $employeeUser->id,
            'team_id' => $team->id,
        ]);

        [$otherUser] = $this->makeMember($enterprise, 'employee');
        $foreignLead = $this->makeLead($enterprise, [
            'assigned_to' => $otherUser->id,
        ]);

        Sanctum::actingAs($employeeUser);

        $response = $this->post('/api/v1/enterprise/calls/import', [
            'lead_id' => $ownLead->id,
            'recording' => $this->recordingFile('RIFF' . str_repeat("\x00", 2048)),
            'recorded_at' => now()->subMinutes(10)->toIso8601String(),
            'duration_seconds' => 90,
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.edition', CallLog::EDITION_ENTERPRISE);

        $this->assertSame($employeeUser->id, (int) CallLog::find($response->json('data.id'))->initiated_by);

        $this->post('/api/v1/enterprise/calls/import', [
            'lead_id' => $foreignLead->id,
            'recording' => $this->recordingFile('RIFF' . str_repeat("\x01", 2048)),
            'recorded_at' => now()->toIso8601String(),
            'duration_seconds' => 60,
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->getJson('/api/v1/enterprise/calls?lead_id=' . $ownLead->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_empty_map_fields_are_encoded_as_json_objects_for_mobile_clients(): void
    {
        $realtor = $this->makeRealtor();

        Sanctum::actingAs($realtor);

        $dashboard = $this->getJson('/api/v1/realtor/dashboard')->assertOk();
        $this->assertStringContainsString('"by_status":{}', $dashboard->getContent());
        $this->assertStringContainsString('"by_user":[]', $dashboard->getContent());

        $summary = $this->getJson('/api/v1/realtor/calls/summary')->assertOk();
        $this->assertStringContainsString('"by_status":{}', $summary->getContent());

        $calls = $this->getJson('/api/v1/realtor/calls')->assertOk();
        $this->assertStringContainsString('"status_counts":{}', $calls->getContent());

        $tasks = $this->getJson('/api/v1/realtor/tasks')->assertOk();
        $this->assertStringContainsString('"status_counts":{}', $tasks->getContent());

        $visits = $this->getJson('/api/v1/realtor/site-visits')->assertOk();
        $this->assertStringContainsString('"status_counts":{}', $visits->getContent());

        $leads = $this->getJson('/api/v1/realtor/leads')->assertOk();
        $this->assertStringContainsString('"status_counts":{}', $leads->getContent());
    }
}
