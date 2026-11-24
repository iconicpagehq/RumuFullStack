<?php

namespace Tests\Concerns;

use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\InventoryWing;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Support\Str;

trait BuildsEnterpriseWorkspace
{
    protected function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'key' => 'enterprise-test-' . Str::lower(Str::random(8)),
            'name' => 'Enterprise Test ' . Str::upper(Str::random(6)),
            'audience' => 'enterprise',
            'max_campaigns' => null,
            'max_leads_per_campaign' => null,
            'max_managers' => 5,
            'max_employees' => 20,
            'max_teams' => 5,
            'max_monthly_leads' => 500,
            'max_inventory_units' => 200,
            'advanced_reports' => true,
            'meta_integrations' => false,
            'is_default' => false,
            'is_active' => true,
            'price' => 4999,
            'sort_order' => 10,
        ], $overrides));
    }

    /**
     * @return array{0: Enterprise, 1: User, 2: EnterpriseMembership}
     */
    protected function makeEnterprise(?Plan $plan = null, ?User $admin = null, string $name = 'Acme Realty'): array
    {
        $plan ??= $this->makePlan();
        $admin ??= User::factory()->create([
            'role' => 'enterprise',
            'plan_id' => $plan->id,
            'password' => bcrypt('password123'),
        ]);

        $enterprise = Enterprise::create([
            'name' => $name,
            'email' => Str::lower(Str::random(8)) . '@enterprise.test',
            'owner_id' => $admin->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
            'settings' => [],
        ]);

        $membership = EnterpriseMembership::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return [$enterprise, $admin, $membership];
    }

    /**
     * @return array{0: User, 1: EnterpriseMembership}
     */
    protected function makeMember(Enterprise $enterprise, string $role, ?Team $team = null): array
    {
        $user = User::factory()->create([
            'role' => 'enterprise',
            'plan_id' => $enterprise->plan_id,
        ]);

        $membership = EnterpriseMembership::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        if ($team) {
            TeamMembership::create([
                'enterprise_id' => $enterprise->id,
                'team_id' => $team->id,
                'user_id' => $user->id,
                'is_primary' => true,
                'joined_at' => now(),
            ]);
        }

        return [$user, $membership];
    }

    protected function makeTeam(Enterprise $enterprise, ?User $manager = null, string $name = 'Sales Team'): Team
    {
        return Team::create([
            'enterprise_id' => $enterprise->id,
            'name' => $name,
            'manager_id' => $manager?->id,
            'status' => 'active',
            'lead_distribution_mode' => 'manual',
        ]);
    }

    protected function makeLead(Enterprise $enterprise, array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'enterprise_id' => $enterprise->id,
            'name' => 'Test Lead',
            'phone' => '98765' . random_int(10000, 99999),
            'email' => Str::lower(Str::random(8)) . '@lead.test',
            'status' => 'new',
            'enterprise_stage' => 'new',
            'priority' => 'medium',
            'source' => 'manual',
        ], $overrides));
    }

    /**
     * @return array{0: InventoryProject, 1: InventoryTower, 2: InventoryWing, 3: InventoryFloor, 4: InventoryUnit}
     */
    protected function makeInventory(Enterprise $enterprise, User $owner): array
    {
        $project = InventoryProject::create([
            'user_id' => $owner->id,
            'enterprise_id' => $enterprise->id,
            'name' => 'Skyline Heights ' . Str::random(4),
            'city' => 'Pune',
            'status' => 'active',
        ]);

        $tower = InventoryTower::create([
            'inventory_project_id' => $project->id,
            'name' => 'Tower A',
        ]);

        $wing = InventoryWing::create([
            'inventory_tower_id' => $tower->id,
            'name' => 'Wing A',
        ]);

        $floor = InventoryFloor::create([
            'inventory_tower_id' => $tower->id,
            'inventory_wing_id' => $wing->id,
            'floor_number' => 1,
        ]);

        $unit = InventoryUnit::create([
            'inventory_floor_id' => $floor->id,
            'unit_number' => 'A-' . random_int(100, 999),
            'layout' => '2_bhk',
            'base_price' => 5500000,
            'availability_status' => 'available',
        ]);

        return [$project, $tower, $wing, $floor, $unit];
    }
}
