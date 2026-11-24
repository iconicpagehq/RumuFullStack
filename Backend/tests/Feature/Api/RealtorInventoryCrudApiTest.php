<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\InventoryProject;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtorInventoryCrudApiTest extends TestCase
{
    use RefreshDatabase;

    private function createRealtor(): User
    {
        $plan = Plan::factory()->basic()->create();

        return User::factory()->create([
            'role' => 'realtor',
            'plan_id' => $plan->id,
        ]);
    }

    public function test_realtor_can_create_update_delete_project(): void
    {
        $realtor = $this->createRealtor();
        Sanctum::actingAs($realtor);

        $create = $this->postJson('/api/v1/realtor/inventory/projects', [
            'name' => 'API Project',
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $projectId = $create->json('data.id');
        $this->assertNotEmpty($projectId);

        $this->putJson("/api/v1/realtor/inventory/projects/{$projectId}", [
            'name' => 'API Project Updated',
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.name', 'API Project Updated');

        $this->deleteJson("/api/v1/realtor/inventory/projects/{$projectId}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_realtor_project_forbidden_for_other_realtor(): void
    {
        $plan = Plan::factory()->basic()->create();
        $realtor = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);
        $other = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);

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
}

