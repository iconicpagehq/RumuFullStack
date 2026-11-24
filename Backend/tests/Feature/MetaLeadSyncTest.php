<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaLeadSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createRealtor(array $attributes = []): User
    {
        $plan = Plan::factory()->create([
            'key' => 'basic',
            'name' => 'Basic',
            'max_campaigns' => 5,
            'max_leads_per_campaign' => 2, // Low limit to test plan enforcement
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

        if ($user->facebook_page_id) {
            \App\Models\MetaPage::create([
                'user_id' => $user->id,
                'page_id' => $user->facebook_page_id,
                'page_name' => 'Test Page',
                'access_token' => $user->facebook_page_access_token,
                'status' => 'connected',
                'token_last_synced_at' => now(),
            ]);
        }

        return $user;
    }

    public function test_web_list_meta_forms_returns_forms_successfully(): void
    {
        Http::fake([
            'graph.facebook.com/*/page-123/leadgen_forms*' => Http::response([
                'data' => [
                    ['id' => 'form-101', 'name' => 'Web Form A', 'status' => 'ACTIVE'],
                    ['id' => 'form-102', 'name' => 'Web Form B', 'status' => 'ACTIVE'],
                ]
            ], 200),
        ]);

        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        $response = $this->actingAs($realtor)
            ->get("/campaigns/{$campaign->id}/meta/forms?page_id=page-123");

        $response->assertOk()
            ->assertJsonPath('forms.0.id', 'form-101')
            ->assertJsonPath('forms.0.name', 'Web Form A')
            ->assertJsonCount(2, 'forms');
    }

    public function test_web_list_meta_forms_fails_if_facebook_not_linked(): void
    {
        $realtor = $this->createRealtor([
            'facebook_id' => null,
            'facebook_page_id' => null,
            'facebook_page_access_token' => null,
        ]);
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        $response = $this->actingAs($realtor)
            ->get("/campaigns/{$campaign->id}/meta/forms?page_id=page-123");

        $response->assertStatus(404)
            ->assertJsonStructure(['error']);
    }

    public function test_web_sync_meta_leads_imports_leads_successfully(): void
    {
        Http::fake([
            'graph.facebook.com/*/form-101/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-lead-001',
                        'created_time' => '2026-07-12T10:00:00+0000',
                        'field_data' => [
                            ['name' => 'full_name', 'values' => ['Alice Meta']],
                            ['name' => 'email', 'values' => ['alice@example.com']],
                            ['name' => 'phone_number', 'values' => ['+919876543210']],
                        ]
                    ]
                ]
            ], 200),
        ]);

        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);
        
        $realtor->metaPages()->create([
            'page_id' => 'page-101',
            'page_name' => 'Test Page',
            'access_token' => 'test-token',
            'status' => 'connected',
        ]);

        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-101',
            'form_name' => 'Web Form A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($realtor)
            ->post("/campaigns/{$campaign->id}/meta/sync", [
                'form_id' => 'form-101',
                'page_id' => 'page-101',
            ]);

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('success', 'Synced 1 lead(s) from Meta.');

        $this->assertDatabaseHas('leads', [
            'campaign_id' => $campaign->id,
            'name' => 'Alice Meta',
            'email' => 'alice@example.com',
            'phone' => '+919876543210',
            'meta_lead_id' => 'meta-lead-001',
            'source' => 'meta_ads',
        ]);

        $this->assertDatabaseHas('meta_campaign_forms', [
            'campaign_id' => $campaign->id,
            'form_id' => 'form-101',
        ]);

        $campaign->refresh();
        $this->assertEquals('form-101', $campaign->meta_lead_form_id);
    }

    public function test_web_sync_meta_leads_skips_duplicates(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        $realtor->metaPages()->create([
            'page_id' => 'page-101',
            'page_name' => 'Test Page',
            'access_token' => 'test-token',
            'status' => 'connected',
        ]);

        // Pre-create duplicate lead
        Lead::factory()->create([
            'campaign_id' => $campaign->id,
            'meta_lead_id' => 'meta-lead-exists',
        ]);

        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-101',
            'form_name' => 'Web Form A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'graph.facebook.com/*/form-101/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-lead-exists',
                        'created_time' => '2026-07-12T10:00:00+0000',
                        'field_data' => [
                            ['name' => 'full_name', 'values' => ['Existing Lead']],
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($realtor)
            ->post("/campaigns/{$campaign->id}/meta/sync", [
                'form_id' => 'form-101',
                'page_id' => 'page-101',
            ]);

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('success', 'Synced 0 lead(s) from Meta. Skipped 1 duplicate(s).');
    }

    public function test_web_sync_meta_leads_enforces_plan_limits(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        $realtor->metaPages()->create([
            'page_id' => 'page-101',
            'page_name' => 'Test Page',
            'access_token' => 'test-token',
            'status' => 'connected',
        ]);

        // Campaign has 1 lead, limit is 2 (so we can import 1 more, but not 2 more)
        Lead::factory()->create(['campaign_id' => $campaign->id]);

        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-101',
            'form_name' => 'Web Form A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'graph.facebook.com/*/form-101/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-lead-first-importable',
                        'created_time' => '2026-07-12T10:00:00+0000',
                        'field_data' => [
                            ['name' => 'full_name', 'values' => ['First Importable Lead']],
                        ]
                    ],
                    [
                        'id' => 'meta-lead-overflow',
                        'created_time' => '2026-07-12T10:01:00+0000',
                        'field_data' => [
                            ['name' => 'full_name', 'values' => ['Overflow Lead']],
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($realtor)
            ->post("/campaigns/{$campaign->id}/meta/sync", [
                'form_id' => 'form-101',
                'page_id' => 'page-101',
            ]);

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('warning', 'Synced 1 lead(s) from Meta. Plan limit reached. Some leads were not imported.');
    }

    public function test_api_list_meta_forms_returns_forms_successfully(): void
    {
        Http::fake([
            'graph.facebook.com/*/page-123/leadgen_forms*' => Http::response([
                'data' => [
                    ['id' => 'form-201', 'name' => 'API Form A', 'status' => 'ACTIVE'],
                ]
            ], 200),
        ]);

        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        $response = $this->actingAs($realtor, 'sanctum')
            ->getJson("/api/v1/realtor/campaigns/{$campaign->id}/meta/forms?page_id=page-123");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.forms.0.id', 'form-201');
    }

    public function test_api_sync_meta_leads_imports_leads_successfully(): void
    {
        Http::fake([
            'graph.facebook.com/*/form-201/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-lead-999',
                        'created_time' => '2026-07-12T10:00:00+0000',
                        'field_data' => [
                            ['name' => 'full_name', 'values' => ['Bob Api']],
                            ['name' => 'email', 'values' => ['bob@example.com']],
                        ]
                    ]
                ]
            ], 200),
        ]);

        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-201',
            'form_name' => 'API Form A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($realtor, 'sanctum')
            ->postJson("/api/v1/realtor/campaigns/{$campaign->id}/meta/sync", [
                'form_id' => 'form-201',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 1);

        $this->assertDatabaseHas('leads', [
            'campaign_id' => $campaign->id,
            'name' => 'Bob Api',
            'meta_lead_id' => 'meta-lead-999',
        ]);
    }
}
