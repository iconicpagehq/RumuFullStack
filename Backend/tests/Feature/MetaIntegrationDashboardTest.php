<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetaIntegrationDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createRealtor(): User
    {
        $user = User::factory()->create([
            'role' => 'realtor',
            'facebook_id' => 'fb-123',
            'facebook_page_id' => 'page-123',
            'facebook_page_name' => 'My Real Estate Page',
            'facebook_page_access_token' => 'page-token-abc',
        ]);

        \App\Models\MetaPage::create([
            'user_id' => $user->id,
            'page_id' => 'page-123',
            'page_name' => 'My Real Estate Page',
            'access_token' => 'page-token-abc',
            'status' => 'connected',
        ]);

        return $user;
    }

    public function test_dashboard_loads_with_correct_data(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'form-888',
        ]);

        DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-888',
            'form_name' => 'Lead Gen Form',
            'last_webhook_received_at' => now()->subMinutes(5),
            'last_manual_sync_at' => null,
            'updated_at' => now(),
        ]);

        \App\Models\MetaSyncLog::create([
            'user_id' => $realtor->id,
            'campaign_id' => $campaign->id,
            'type' => 'success',
            'event_type' => 'webhook',
            'message' => 'Lead successfully imported via webhook',
        ]);

        $response = $this->actingAs($realtor)->get(route('campaigns.meta-integration', $campaign));

        $response->assertStatus(200);
        $response->assertSee('Meta Integration');
        $response->assertSee('My Real Estate Page');
        $response->assertSee('Lead Gen Form');
        $response->assertSee('form-888');
        $response->assertSee('Webhook: Active');
        $response->assertSee('Lead successfully imported via webhook');
    }

    public function test_disconnect_form_clears_mapping_and_logs_event(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create([
            'user_id' => $realtor->id,
            'meta_lead_form_id' => 'form-888',
        ]);

        DB::table('meta_campaign_forms')->insert([
            'campaign_id' => $campaign->id,
            'page_id' => 'page-123',
            'form_id' => 'form-888',
            'form_name' => 'Lead Gen Form',
        ]);

        $response = $this->actingAs($realtor)->post(route('campaigns.meta-integration.disconnect-form', $campaign));

        $response->assertRedirect(route('campaigns.meta-integration', $campaign));
        
        $campaign->refresh();
        $this->assertNull($campaign->meta_lead_form_id);

        $this->assertDatabaseMissing('meta_campaign_forms', [
            'campaign_id' => $campaign->id,
        ]);

        $this->assertDatabaseHas('meta_sync_logs', [
            'user_id' => $realtor->id,
            'campaign_id' => $campaign->id,
            'event_type' => 'disconnected',
        ]);
    }
}
