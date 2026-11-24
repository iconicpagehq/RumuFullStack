<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookLeadJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.webhook_verify_token' => 'my-secret-token',
            'services.meta.app_secret' => 'my-app-secret',
        ]);
    }

    private function createRealtor(array $attributes = []): User
    {
        $plan = Plan::factory()->create([
            'key' => 'basic',
            'name' => 'Basic',
            'max_campaigns' => 5,
            'max_leads_per_campaign' => 10,
            'is_default' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(array_merge([
            'role' => 'realtor',
            'plan_id' => $plan->id,
            'facebook_id' => 'fb-user-123',
            'facebook_page_id' => 'page-123',
            'facebook_page_access_token' => 'page-token-abc',
        ], $attributes));

        \App\Models\MetaPage::create([
            'user_id' => $user->id,
            'page_id' => 'page-123',
            'page_name' => 'Test Page',
            'access_token' => 'page-token-abc',
            'status' => 'connected',
        ]);

        return $user;
    }

    public function test_webhook_verification_succeeds_with_correct_token(): void
    {
        $response = $this->get('/meta/webhook?hub_mode=subscribe&hub_verify_token=my-secret-token&hub_challenge=challenge-xyz');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('challenge-xyz');
    }

    public function test_webhook_verification_fails_with_incorrect_token(): void
    {
        $response = $this->get('/meta/webhook?hub_mode=subscribe&hub_verify_token=wrong-token&hub_challenge=challenge-xyz');

        $response->assertStatus(403);
    }

    public function test_webhook_post_fails_with_missing_or_invalid_signature(): void
    {
        $payload = ['object' => 'page'];

        $response = $this->postJson('/meta/webhook', $payload, [
            'X-Hub-Signature-256' => 'sha256=invalid-signature-hash',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_post_queues_job_successfully_with_valid_signature(): void
    {
        Queue::fake();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page-123',
                    'time' => 1445831517,
                    'changes' => [
                        [
                            'field' => 'leadgen',
                            'value' => [
                                'form_id' => 'form-888',
                                'leadgen_id' => 'meta-lead-777',
                                'created_time' => 1445831510,
                                'page_id' => 'page-123',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, 'my-app-secret');

        // Use call() to send a raw JSON body so the signature matches exactly
        $response = $this->call('POST', '/meta/webhook', [], [], [], [
            'HTTP_X-Hub-Signature-256' => "sha256={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody);

        $response->assertOk()
            ->assertSee('Webhook Handled');

        Queue::assertPushed(ProcessWebhookLeadJob::class, function ($job) {
            return $job->pageId === 'page-123'
                && $job->formId === 'form-888'
                && $job->leadId === 'meta-lead-777'
                && $job->queue === 'meta-leads';
        });
    }

    public function test_job_imports_lead_successfully(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'form-888',
        ]);
        
        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-888',
            'form_name' => 'Test Form',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'meta-lead-777',
                'created_time' => '2026-07-12T10:00:00+0000',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Alice Webhook']],
                    ['name' => 'email', 'values' => ['alice.webhook@example.com']],
                ]
            ], 200),
        ]);

        $job = new ProcessWebhookLeadJob('page-123', 'form-888', 'meta-lead-777');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $this->assertDatabaseHas('leads', [
            'campaign_id' => $campaign->id,
            'name' => 'Alice Webhook',
            'email' => 'alice.webhook@example.com',
            'meta_lead_id' => 'meta-lead-777',
            'source' => 'meta_ads',
        ]);
    }

    public function test_job_skips_duplicate_checking_idempotently_before_api_call(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'form-888',
        ]);
        
        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-888',
            'form_name' => 'Test Form',
        ]);

        // Pre-create duplicate lead
        Lead::factory()->create([
            'campaign_id' => $campaign->id,
            'meta_lead_id' => 'meta-lead-777',
        ]);

        // Http should receive no calls since idempotency exits early
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 500),
        ]);

        $job = new ProcessWebhookLeadJob('page-123', 'form-888', 'meta-lead-777');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $this->assertDatabaseCount('leads', 1);
        Http::assertNothingSent();
    }

    public function test_job_logs_warning_for_unmapped_campaign(): void
    {
        $realtor = $this->createRealtor();
        
        // Campaign exists, but meta_lead_form_id is unmapped or maps to a different form
        Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'different-form',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([], 500),
        ]);

        $job = new ProcessWebhookLeadJob('page-123', 'form-888', 'meta-lead-777');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $this->assertDatabaseCount('leads', 0);
        Http::assertNothingSent();
    }

    public function test_job_resets_token_specifically_on_expired_oauth_error(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'form-888',
        ]);
        
        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-888',
            'form_name' => 'Test Form',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired on Tuesday, 13-Jun-26 05:00:00 PDT or is otherwise invalid.',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'error_subcode' => 463
                ]
            ], 400),
        ]);

        $job = new ProcessWebhookLeadJob('page-123', 'form-888', 'meta-lead-777');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $realtor->refresh();
        $this->assertTrue((bool)$realtor->facebook_token_expired);
        
        $metaPage = \App\Models\MetaPage::where('page_id', 'page-123')->first();
        $this->assertEquals('expired', $metaPage->status);
    }

    public function test_job_does_not_reset_token_on_temporary_api_failures(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'form-888',
        ]);
        
        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-888',
            'form_name' => 'Test Form',
        ]);

        // Mock a transient non-token API failure (GraphMethodException, code 100)
        // This must NOT match OAuthException codes 102/190/467, so token must be preserved.
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Unsupported get request. Please read the Graph API documentation.',
                    'type' => 'GraphMethodException',
                    'code' => 100
                ]
            ], 400),
        ]);

        $job = new ProcessWebhookLeadJob('page-123', 'form-888', 'meta-lead-777');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $realtor->refresh();
        $this->assertNotNull($realtor->facebook_page_access_token);
        
        $metaPage = \App\Models\MetaPage::where('page_id', 'page-123')->first();
        $this->assertEquals('connected', $metaPage->status);
    }
}
