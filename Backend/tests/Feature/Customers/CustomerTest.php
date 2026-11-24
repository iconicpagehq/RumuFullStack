<?php

namespace Tests\Feature\Customers;

use App\Models\Campaign;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function createRealtorSetup(): array
    {
        $plan = Plan::factory()->create();
        $realtor = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);
        $otherRealtor = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);
        $otherCampaign = Campaign::factory()->create(['user_id' => $otherRealtor->id]);

        $project = InventoryProject::create([
            'user_id' => $realtor->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
        ]);
        $tower = $project->towers()->create(['name' => 'T1', 'sort_order' => 1]);
        $floor = $tower->floors()->create(['floor_number' => 1]);

        return compact('realtor', 'otherRealtor', 'admin', 'campaign', 'otherCampaign', 'floor');
    }

    public function test_won_lead_appears_for_owning_realtor(): void
    {
        $data = $this->createRealtorSetup();

        $lead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'name' => 'Won Customer',
            'status' => 'won',
        ]);

        $this->actingAs($data['realtor'])
            ->get(route('realtor.customers'))
            ->assertOk()
            ->assertSee('Won Customer');
    }

    public function test_lead_with_booked_unit_appears_even_if_not_won(): void
    {
        $data = $this->createRealtorSetup();

        $unit = $data['floor']->units()->create([
            'unit_number' => '201',
            'availability_status' => InventoryUnit::STATUS_BOOKED,
        ]);

        Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'name' => 'Booked Customer',
            'status' => 'negotiation',
            'inventory_unit_id' => $unit->id,
        ]);

        $this->actingAs($data['realtor'])
            ->get(route('realtor.customers'))
            ->assertOk()
            ->assertSee('Booked Customer');
    }

    public function test_new_lead_without_unit_does_not_appear(): void
    {
        $data = $this->createRealtorSetup();

        Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'name' => 'Plain Lead',
            'status' => 'new',
        ]);

        $this->actingAs($data['realtor'])
            ->get(route('realtor.customers'))
            ->assertOk()
            ->assertDontSee('Plain Lead');
    }

    public function test_realtor_cannot_see_other_realtors_customers(): void
    {
        $data = $this->createRealtorSetup();

        Lead::factory()->create([
            'campaign_id' => $data['otherCampaign']->id,
            'name' => 'Other Realtor Customer',
            'status' => 'won',
        ]);

        $this->actingAs($data['realtor'])
            ->get(route('realtor.customers'))
            ->assertOk()
            ->assertDontSee('Other Realtor Customer');
    }

    public function test_non_realtor_cannot_access_customers_page(): void
    {
        $data = $this->createRealtorSetup();

        $this->actingAs($data['admin'])
            ->get(route('realtor.customers'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_is_customer_helper(): void
    {
        $data = $this->createRealtorSetup();

        $wonLead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'status' => 'won',
        ]);

        $newLead = Lead::factory()->create([
            'campaign_id' => $data['campaign']->id,
            'status' => 'new',
        ]);

        $this->assertTrue($wonLead->isCustomer());
        $this->assertFalse($newLead->isCustomer());
    }
}
