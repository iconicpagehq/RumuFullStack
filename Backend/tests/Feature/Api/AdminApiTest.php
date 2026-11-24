<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_endpoints_require_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $realtor = User::factory()->create(['role' => 'realtor']);

        Sanctum::actingAs($realtor);
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_and_toggle_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/plans', [
            'name' => 'Test Plan',
            'price' => 0,
            'max_campaigns' => 5,
            'max_leads_per_campaign' => 10,
            'is_active' => true,
            'is_default' => false,
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $plan = Plan::where('name', 'Test Plan')->firstOrFail();

        $this->patchJson("/api/v1/admin/plans/{$plan->id}/toggle-status")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}

