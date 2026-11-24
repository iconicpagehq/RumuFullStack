<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessWebhookLeadJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MetaCapiSetting;
use App\Models\MetaPage;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class EnterpriseMetaTest extends TestCase
{
    use BuildsEnterpriseWorkspace;
    use RefreshDatabase;

    private function makeCampaign($enterprise, $user, $team, string $name = 'Enterprise Meta Campaign'): Campaign
    {
        return Campaign::create([
            'user_id' => $user->id,
            'enterprise_id' => $enterprise->id,
            'team_id' => $team->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function makePage($enterprise, $user, string $pageId, string $status = 'connected', string $token = 'page-token'): MetaPage
    {
        return MetaPage::create([
            'user_id' => $user->id,
            'enterprise_id' => $enterprise->id,
            'page_id' => $pageId,
            'page_name' => 'Page ' . $pageId,
            'access_token' => $token,
            'status' => $status,
            'token_last_synced_at' => now(),
        ]);
    }

    public function test_enterprise_meta_pages_are_scoped_and_role_limited(): void
    {
        $plan = $this->makePlan(['meta_integrations' => true]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);
        $manager = $this->makeMember($enterprise, 'manager')[0];
        $employee = $this->makeMember($enterprise, 'employee')[0];
        $this->makePage($enterprise, $admin, 'page-ent-a');

        [$otherEnterprise, $otherAdmin] = $this->makeEnterprise($plan, name: 'Other Meta Realty');
        $this->makePage($otherEnterprise, $otherAdmin, 'page-ent-b');

        $realtor = \App\Models\User::factory()->create(['role' => 'realtor']);
        MetaPage::create([
            'user_id' => $realtor->id,
            'page_id' => 'page-realtor',
            'page_name' => 'Realtor Page',
            'access_token' => 'realtor-token',
            'status' => 'connected',
        ]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/enterprise/meta/pages')->assertOk();
        $pageIds = collect($response->json('data.pages'))->pluck('page_id')->all();
        $this->assertSame(['page-ent-a'], $pageIds);

        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/enterprise/meta/pages')->assertOk();

        Sanctum::actingAs($employee);
        $this->getJson('/api/v1/enterprise/meta/pages')->assertStatus(403);
    }

    public function test_meta_mapping_rejects_foreign_pages_and_saves_capi_settings(): void
    {
        $plan = $this->makePlan(['meta_integrations' => true]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);
        $team = $this->makeTeam($enterprise, $admin);
        $campaign = $this->makeCampaign($enterprise, $admin, $team);
        $this->makePage($enterprise, $admin, 'page-ent-a');

        [$otherEnterprise, $otherAdmin] = $this->makeEnterprise($plan, name: 'Foreign Meta Realty');
        $this->makePage($otherEnterprise, $otherAdmin, 'page-ent-b');
        $foreignCampaign = $this->makeCampaign($otherEnterprise, $otherAdmin, $this->makeTeam($otherEnterprise, $otherAdmin), 'Foreign Campaign');

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta/mapping", [
            'page_id' => 'page-ent-b',
            'form_id' => 'form-1',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('page_id');

        $this->postJson("/api/v1/enterprise/campaigns/{$foreignCampaign->id}/meta/mapping", [
            'page_id' => 'page-ent-a',
            'form_id' => 'form-1',
        ])->assertStatus(404);

        $this->postJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta/mapping", [
            'page_id' => 'page-ent-a',
            'form_id' => 'form-1',
            'form_name' => 'Enterprise Form',
        ])->assertOk();

        $this->assertDatabaseHas('meta_campaign_forms', [
            'campaign_id' => $campaign->id,
            'page_id' => 'page-ent-a',
            'form_id' => 'form-1',
        ]);

        $this->getJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta-integration")
            ->assertOk()
            ->assertJsonPath('data.selected_page_id', 'page-ent-a')
            ->assertJsonPath('data.form.form_id', 'form-1');

        $this->postJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta-integration/capi", [
            'pixel_id' => 'pixel-123',
            'access_token' => 'capi-token',
            'test_event_code' => 'TEST123',
            'is_active' => true,
            'status_mapping' => ['new' => 'Lead'],
        ])->assertOk()
            ->assertJsonPath('data.pixel_id', 'pixel-123')
            ->assertJsonPath('data.has_access_token', true);

        $this->assertDatabaseHas('meta_capi_settings', [
            'campaign_id' => $campaign->id,
            'pixel_id' => 'pixel-123',
        ]);

        $settings = MetaCapiSetting::where('campaign_id', $campaign->id)->firstOrFail();
        $this->assertTrue((bool) $settings->is_active);
        $this->assertSame(['new' => 'Lead'], $settings->status_mapping);
    }

    public function test_meta_forms_are_fetched_for_the_connected_enterprise_page(): void
    {
        $plan = $this->makePlan(['meta_integrations' => true]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);
        $team = $this->makeTeam($enterprise, $admin);
        $campaign = $this->makeCampaign($enterprise, $admin, $team);
        $this->makePage($enterprise, $admin, 'page-ent-a');

        Http::fake([
            'graph.facebook.com/*/page-ent-a/leadgen_forms*' => Http::response([
                'data' => [
                    ['id' => 'form-101', 'name' => 'Enterprise Form A', 'status' => 'ACTIVE'],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta/forms?page_id=page-ent-a")
            ->assertOk()
            ->assertJsonPath('data.forms.0.id', 'form-101')
            ->assertJsonPath('data.page.page_id', 'page-ent-a');
    }

    public function test_meta_sync_imports_leads_into_the_enterprise_campaign(): void
    {
        $plan = $this->makePlan(['meta_integrations' => true]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);
        $team = $this->makeTeam($enterprise, $admin);
        $campaign = $this->makeCampaign($enterprise, $admin, $team);
        $this->makePage($enterprise, $admin, 'page-ent-a');

        Http::fake([
            'graph.facebook.com/*/form-1/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-lead-sync-1',
                        'created_time' => '2026-10-20T10:00:00+0000',
                        'field_data' => [
                            ['name' => 'full_name', 'values' => ['Nisha Enterprise']],
                            ['name' => 'email', 'values' => ['nisha@enterprise.test']],
                            ['name' => 'phone_number', 'values' => ['+919876500001']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta/mapping", [
            'page_id' => 'page-ent-a',
            'form_id' => 'form-1',
        ])->assertOk();

        $this->postJson("/api/v1/enterprise/campaigns/{$campaign->id}/meta/sync")
            ->assertOk()
            ->assertJsonPath('data.imported', 1);

        $lead = Lead::where('meta_lead_id', 'meta-lead-sync-1')->firstOrFail();
        $this->assertSame($enterprise->id, $lead->enterprise_id);
        $this->assertSame($team->id, $lead->team_id);
        $this->assertSame($campaign->id, $lead->campaign_id);
        $this->assertSame('meta_ads', $lead->source);
        $this->assertSame('new', $lead->enterprise_stage);
    }

    public function test_enterprise_webhook_job_imports_leads_and_blocks_cross_tenant_pages(): void
    {
        $plan = $this->makePlan(['meta_integrations' => true]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);
        $team = $this->makeTeam($enterprise, $admin);
        $campaign = $this->makeCampaign($enterprise, $admin, $team);
        $this->makePage($enterprise, $admin, 'page-hook-a');

        DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-hook-a',
            'form_id' => 'form-hook',
            'form_name' => 'Webhook Form',
        ]);

        Http::fake([
            'graph.facebook.com/*/meta-lead-hook-1*' => Http::response([
                'id' => 'meta-lead-hook-1',
                'created_time' => '2026-10-21T09:00:00+0000',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Webhook Enterprise Lead']],
                    ['name' => 'email', 'values' => ['webhook@enterprise.test']],
                    ['name' => 'phone_number', 'values' => ['+919876500002']],
                ],
            ], 200),
        ]);

        $job = new ProcessWebhookLeadJob('page-hook-a', 'form-hook', 'meta-lead-hook-1');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $lead = Lead::where('meta_lead_id', 'meta-lead-hook-1')->firstOrFail();
        $this->assertSame($enterprise->id, $lead->enterprise_id);
        $this->assertSame($team->id, $lead->team_id);

        [$foreignEnterprise, $foreignAdmin] = $this->makeEnterprise($plan, name: 'Foreign Webhook Realty');
        $foreignTeam = $this->makeTeam($foreignEnterprise, $foreignAdmin);
        $foreignCampaign = $this->makeCampaign($foreignEnterprise, $foreignAdmin, $foreignTeam, 'Foreign Webhook Campaign');

        DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $foreignCampaign->id,
            'page_id' => 'page-hook-a',
            'form_id' => 'form-hook-2',
            'form_name' => 'Cross Tenant Form',
        ]);

        $job = new ProcessWebhookLeadJob('page-hook-a', 'form-hook-2', 'meta-lead-hook-2');
        $job->handle(app(MetaLeadApiService::class), app(MetaLeadImportService::class));

        $this->assertDatabaseMissing('leads', ['meta_lead_id' => 'meta-lead-hook-2']);
    }
}
