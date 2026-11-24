<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtorApiTest extends TestCase
{
    use RefreshDatabase;

    private function createRealtor(?Plan $plan = null): User
    {
        $plan ??= Plan::factory()->basic()->create();

        return User::factory()->create([
            'role' => 'realtor',
            'plan_id' => $plan->id,
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_register_and_login_return_token(): void
    {
        Plan::factory()->create(['is_default' => true, 'is_active' => true]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'API Realtor',
            'email' => 'api@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer');
    }

    public function test_protected_route_rejects_missing_token(): void
    {
        $this->getJson('/api/v1/realtor/dashboard')->assertUnauthorized();
    }

    public function test_admin_can_login_with_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => bcrypt('password123')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_realtor_lists_own_campaigns_only(): void
    {
        $plan = Plan::factory()->basic()->create();
        $realtor = $this->createRealtor($plan);
        $other = $this->createRealtor($plan);

        $mine = Campaign::factory()->create(['user_id' => $realtor->id, 'name' => 'My Campaign']);
        Campaign::factory()->create(['user_id' => $other->id, 'name' => 'Other Campaign']);

        Sanctum::actingAs($realtor);

        $this->getJson('/api/v1/realtor/campaigns')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'My Campaign'])
            ->assertJsonMissing(['name' => 'Other Campaign']);
    }

    public function test_create_lead_on_campaign(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $this->postJson("/api/v1/realtor/campaigns/{$campaign->id}/leads", [
            'name' => 'API Lead',
            'status' => 'new',
            'phone' => '9876543210',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'API Lead');
    }

    public function test_customers_list_returns_won_lead(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Lead::factory()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Won Customer API',
            'status' => 'won',
        ]);

        Sanctum::actingAs($realtor);

        $this->getJson('/api/v1/realtor/customers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Won Customer API']);
    }

    public function test_inventory_project_forbidden_for_other_realtor(): void
    {
        $plan = Plan::factory()->basic()->create();
        $realtor = $this->createRealtor($plan);
        $other = $this->createRealtor($plan);

        $project = InventoryProject::create([
            'user_id' => $other->id,
            'name' => 'Private Project',
            'slug' => 'private-project',
            'status' => 'active',
        ]);

        Sanctum::actingAs($realtor);

        $this->getJson("/api/v1/realtor/inventory/projects/{$project->id}")
            ->assertForbidden();
    }

    public function test_suspended_subscription_blocks_lead_create(): void
    {
        $realtor = $this->createRealtor();
        $realtor->update(['subscription_suspended_at' => now()]);
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $this->postJson("/api/v1/realtor/campaigns/{$campaign->id}/leads", [
            'name' => 'Blocked Lead',
            'status' => 'new',
        ])->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_realtor_can_delete_only_own_lead(): void
    {
        $plan = Plan::factory()->basic()->create();
        $realtor = $this->createRealtor($plan);
        $other = $this->createRealtor($plan);

        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);
        $lead = Lead::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Delete Me']);

        $otherCampaign = Campaign::factory()->create(['user_id' => $other->id]);
        $foreignLead = Lead::factory()->create(['campaign_id' => $otherCampaign->id]);

        Sanctum::actingAs($realtor);

        $this->deleteJson("/api/v1/realtor/leads/{$foreignLead->id}")->assertForbidden();

        $this->deleteJson("/api/v1/realtor/leads/{$lead->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertNotSoftDeleted('leads', ['id' => $foreignLead->id]);
    }

    public function test_logout_revokes_token(): void
    {
        $realtor = $this->createRealtor();
        $token = $realtor->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertEquals(0, $realtor->fresh()->tokens()->count());
    }
}
