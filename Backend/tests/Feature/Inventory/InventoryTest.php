<?php

namespace Tests\Feature\Inventory;

use App\Models\Campaign;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use App\Services\InventoryAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function createRealtorWithInventory(): array
    {
        $plan = Plan::factory()->create();
        $realtor = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);
        $other = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);

        $project = InventoryProject::create([
            'user_id' => $realtor->id,
            'name' => 'Sunrise Residency',
            'slug' => 'sunrise-residency',
            'status' => 'active',
        ]);

        $tower = $project->towers()->create(['name' => 'Tower A', 'sort_order' => 1]);
        $floor = $tower->floors()->create(['floor_number' => 1]);
        $unit = $floor->units()->create([
            'unit_number' => '101',
            'layout' => '2_bhk',
            'availability_status' => InventoryUnit::STATUS_AVAILABLE,
        ]);

        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        return compact('realtor', 'other', 'project', 'tower', 'floor', 'unit', 'campaign');
    }

    public function test_realtor_can_view_own_inventory_projects(): void
    {
        $data = $this->createRealtorWithInventory();

        $this->actingAs($data['realtor'])
            ->get(route('inventory.projects.index'))
            ->assertOk()
            ->assertSee('Sunrise Residency');
    }

    public function test_realtor_cannot_view_other_realtors_project(): void
    {
        $data = $this->createRealtorWithInventory();

        $this->actingAs($data['other'])
            ->get(route('inventory.projects.show', $data['project']))
            ->assertForbidden();
    }

    public function test_bulk_unit_creation(): void
    {
        $data = $this->createRealtorWithInventory();

        $this->actingAs($data['realtor'])
            ->post(route('inventory.units.bulk-store', $data['floor']), [
                'prefix' => '10',
                'start_number' => 2,
                'end_number' => 4,
                'layout' => '2_bhk',
            ])
            ->assertRedirect();

        $this->assertEquals(4, $data['floor']->units()->count());
    }

    public function test_assigning_unit_to_lead_sets_hold_status(): void
    {
        $data = $this->createRealtorWithInventory();
        $lead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'status' => 'new',
        ]);

        $service = app(InventoryAvailabilityService::class);
        $service->assignToLead($data['unit'], $lead, $data['realtor']);

        $data['unit']->refresh();
        $this->assertEquals(InventoryUnit::STATUS_HOLD, $data['unit']->availability_status);
        $this->assertEquals($lead->id, $data['unit']->lead_id);
    }

    public function test_won_lead_marks_unit_sold(): void
    {
        $data = $this->createRealtorWithInventory();
        $lead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'status' => 'new',
            'inventory_unit_id' => $data['unit']->id,
        ]);

        $service = app(InventoryAvailabilityService::class);
        $service->assignToLead($data['unit'], $lead, $data['realtor']);

        $lead->update(['status' => 'won']);
        $service->syncFromLeadStatus($lead->fresh(), $data['realtor']);

        $data['unit']->refresh();
        $this->assertEquals(InventoryUnit::STATUS_SOLD, $data['unit']->availability_status);
    }

    public function test_cannot_assign_sold_unit_to_lead(): void
    {
        $data = $this->createRealtorWithInventory();
        $data['unit']->update(['availability_status' => InventoryUnit::STATUS_SOLD]);

        $lead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'status' => 'new',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(InventoryAvailabilityService::class)->assignToLead($data['unit'], $lead, $data['realtor']);
    }

    public function test_lost_lead_releases_unit(): void
    {
        $data = $this->createRealtorWithInventory();
        $lead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'status' => 'new',
            'inventory_unit_id' => $data['unit']->id,
        ]);

        $service = app(InventoryAvailabilityService::class);
        $service->assignToLead($data['unit'], $lead, $data['realtor']);

        $lead->update(['status' => 'lost']);
        $service->syncFromLeadStatus($lead->fresh(), $data['realtor']);

        $data['unit']->refresh();
        $this->assertEquals(InventoryUnit::STATUS_AVAILABLE, $data['unit']->availability_status);
        $this->assertNull($data['unit']->lead_id);
    }
}
