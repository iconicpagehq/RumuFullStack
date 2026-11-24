<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Enterprise;
use App\Models\EnterpriseInvitation;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseEditionTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(array $overrides = []): Plan
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

    private function makeEnterprise(?Plan $plan = null, ?User $admin = null, string $name = 'Acme Realty'): array
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

    private function makeMember(Enterprise $enterprise, string $role, ?Team $team = null): array
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

    private function makeTeam(Enterprise $enterprise, ?User $manager = null, string $name = 'Sales Team'): Team
    {
        return Team::create([
            'enterprise_id' => $enterprise->id,
            'name' => $name,
            'manager_id' => $manager?->id,
            'status' => 'active',
            'lead_distribution_mode' => 'manual',
        ]);
    }

    private function makeLead(Enterprise $enterprise, array $overrides = []): Lead
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

    private function makeInventory(Enterprise $enterprise, User $owner): array
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
            'unit_number' => 'A-101',
            'layout' => '2_bhk',
            'base_price' => 5500000,
            'availability_status' => 'available',
        ]);

        return [$project, $tower, $wing, $floor, $unit];
    }

    private function registrationPayload(Plan $plan, string $email = 'owner@prestige.test'): array
    {
        return [
            'company' => [
                'name' => 'Prestige Group',
                'email' => 'ops+' . Str::lower(Str::random(6)) . '@prestige.test',
                'phone' => '02012345678',
            ],
            'admin' => [
                'name' => 'Asha Kulkarni',
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'plan_id' => $plan->id,
            'terms_accepted' => true,
        ];
    }

    public function test_enterprise_registration_creates_workspace_admin_trial_and_audit_log(): void
    {
        $plan = $this->makePlan(['key' => 'enterprise-registration']);

        $response = $this->postJson('/api/v1/enterprise/register', $this->registrationPayload($plan));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'enterprise')
            ->assertJsonPath('data.membership.role', 'admin')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user', 'enterprise', 'membership']]);

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('enterprises', [
            'name' => 'Prestige Group',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('enterprise_memberships', [
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'status' => 'trial',
            'price' => 0,
        ]);
        $this->assertDatabaseHas('enterprise_audit_logs', [
            'action' => 'enterprise.registered',
        ]);
    }

    public function test_registration_rejects_realtor_plans(): void
    {
        $realtorPlan = Plan::factory()->basic()->create();

        $this->postJson('/api/v1/enterprise/register', $this->registrationPayload($realtorPlan))
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan_id');

        $this->assertDatabaseCount('enterprises', 0);
    }

    public function test_enterprise_login_requires_active_membership(): void
    {
        [$enterprise, $admin, $membership] = $this->makeEnterprise();

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'enterprise')
            ->assertJsonPath('data.user.enterprise_membership.role', 'admin');

        $membership->update(['status' => 'suspended', 'suspended_at' => now()]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_suspended_membership_is_blocked_for_existing_tokens(): void
    {
        [$enterprise, $admin, $membership] = $this->makeEnterprise();

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/enterprise/dashboard')->assertOk();

        $membership->update(['status' => 'suspended', 'suspended_at' => now()]);

        $this->getJson('/api/v1/enterprise/dashboard')->assertStatus(403);
    }

    public function test_context_and_dashboard_are_role_aware(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/enterprise/me')
            ->assertOk()
            ->assertJsonPath('data.membership.role', 'admin')
            ->assertJsonPath('data.enterprise.id', $enterprise->id);
        $this->getJson('/api/v1/enterprise/dashboard')->assertOk();
        $this->getJson('/api/v1/enterprise/reference-data')->assertOk();

        Sanctum::actingAs($employee);
        $this->getJson('/api/v1/enterprise/me')
            ->assertOk()
            ->assertJsonPath('data.membership.role', 'employee');
        $this->getJson('/api/v1/enterprise/dashboard')->assertOk();
    }

    public function test_tenant_isolation_prevents_cross_enterprise_access(): void
    {
        [$enterpriseA, $adminA] = $this->makeEnterprise(name: 'Alpha Realty');
        [$enterpriseB] = $this->makeEnterprise(name: 'Beta Realty');

        $leadA = $this->makeLead($enterpriseA, ['name' => 'Alpha Lead']);
        $leadB = $this->makeLead($enterpriseB, ['name' => 'Beta Lead']);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/v1/enterprise/leads/{$leadB->id}")->assertNotFound();
        $this->getJson("/api/v1/enterprise/leads/{$leadA->id}")->assertOk();

        $this->getJson('/api/v1/enterprise/leads')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment(['name' => 'Alpha Lead'])
            ->assertJsonMissing(['name' => 'Beta Lead']);
    }

    public function test_employee_scope_blocks_other_employees_leads_and_admin_actions(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employeeA] = $this->makeMember($enterprise, 'employee', $team);
        [$employeeB] = $this->makeMember($enterprise, 'employee', $team);

        $leadA = $this->makeLead($enterprise, ['name' => 'A Lead', 'team_id' => $team->id, 'assigned_to' => $employeeA->id]);
        $leadB = $this->makeLead($enterprise, ['name' => 'B Lead', 'team_id' => $team->id, 'assigned_to' => $employeeB->id]);

        Sanctum::actingAs($employeeA);

        $this->getJson("/api/v1/enterprise/leads/{$leadA->id}")->assertOk();
        $this->getJson("/api/v1/enterprise/leads/{$leadB->id}")->assertStatus(403);
        $this->getJson('/api/v1/enterprise/leads')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissing(['name' => 'B Lead']);

        $this->postJson('/api/v1/enterprise/teams', ['name' => 'Unauthorized Team'])->assertStatus(403);
        $this->postJson('/api/v1/enterprise/invitations', [
            'email' => 'hacker@example.test',
            'role' => 'employee',
        ])->assertStatus(403);
        $this->getJson('/api/v1/enterprise/audit-logs')->assertStatus(403);
    }

    public function test_invitation_is_single_use_and_creates_team_membership(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/enterprise/invitations', [
            'email' => 'newhire@example.test',
            'role' => 'employee',
            'team_id' => $team->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'newhire@example.test')
            ->assertJsonPath('data.status', 'pending');

        $inviteUrl = $response->json('data.invite_url');
        $this->assertNotEmpty($inviteUrl);

        $token = basename(parse_url($inviteUrl, PHP_URL_PATH));
        $invitation = EnterpriseInvitation::firstOrFail();
        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        $this->assertNotSame($token, $invitation->token_hash);

        $this->getJson("/api/v1/enterprise/invitations/{$token}")
            ->assertOk()
            ->assertJsonPath('data.invitation.email', 'newhire@example.test');

        $this->postJson("/api/v1/enterprise/invitations/{$token}/accept", [
            'name' => 'New Hire',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('data.membership.role', 'employee');

        $this->assertDatabaseHas('enterprise_memberships', [
            'enterprise_id' => $enterprise->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('team_memberships', [
            'enterprise_id' => $enterprise->id,
            'team_id' => $team->id,
        ]);

        $this->postJson("/api/v1/enterprise/invitations/{$token}/accept", [
            'name' => 'New Hire',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(410);

        $this->getJson("/api/v1/enterprise/invitations/{$token}")->assertStatus(410);
    }

    public function test_admin_can_directly_add_an_employee_with_a_team(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/enterprise/users', [
            'name' => 'Direct Hire',
            'email' => 'DIRECT.HIRE@example.test',
            'phone' => '9876500000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'team_id' => $team->id,
            'designation' => 'Sales Executive',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.role', 'employee')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.user.email', 'direct.hire@example.test')
            ->assertJsonPath('data.team.name', 'Sales Team');

        $user = User::where('email', 'direct.hire@example.test')->firstOrFail();
        $this->assertSame('enterprise', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));

        $this->assertDatabaseHas('enterprise_memberships', [
            'enterprise_id' => $enterprise->id,
            'user_id' => $user->id,
            'role' => 'employee',
            'status' => 'active',
            'designation' => 'Sales Executive',
        ]);
        $this->assertDatabaseHas('team_memberships', [
            'enterprise_id' => $enterprise->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('enterprise_audit_logs', [
            'enterprise_id' => $enterprise->id,
            'action' => 'member.created',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'direct.hire@example.test',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'user']]);

        $this->postJson('/api/v1/enterprise/users', [
            'name' => 'Duplicate Hire',
            'email' => 'direct.hire@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_direct_member_creation_is_admin_only_and_tenant_scoped(): void
    {
        [$enterprise] = $this->makeEnterprise();
        [$manager] = $this->makeMember($enterprise, 'manager');

        [$otherEnterprise] = $this->makeEnterprise(null, null, 'Other Realty');
        $otherTeam = $this->makeTeam($otherEnterprise, null, 'Other Team');

        $payload = [
            'name' => 'Cross Tenant',
            'email' => 'cross.tenant@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
        ];

        Sanctum::actingAs($manager);
        $this->postJson('/api/v1/enterprise/users', $payload)->assertForbidden();

        Sanctum::actingAs($enterprise->owner);
        $this->postJson('/api/v1/enterprise/users', $payload + ['team_id' => $otherTeam->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('team_id');

        $this->assertDatabaseMissing('users', ['email' => 'cross.tenant@example.test']);
    }

    public function test_direct_member_creation_respects_plan_seat_limits(): void
    {
        $plan = $this->makePlan([
            'key' => 'enterprise-direct-seat-limit',
            'max_employees' => 0,
        ]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/users', [
            'name' => 'Over Limit',
            'email' => 'over.limit@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
        ])->assertStatus(422)->assertJsonValidationErrors('limit');

        $this->assertDatabaseMissing('users', ['email' => 'over.limit@example.test']);
    }

    public function test_expired_invitation_and_existing_account_cannot_join(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);

        $expiredToken = Str::random(64);
        EnterpriseInvitation::create([
            'enterprise_id' => $enterprise->id,
            'email' => 'late@example.test',
            'role' => 'employee',
            'team_id' => $team->id,
            'invited_by' => $admin->id,
            'token_hash' => hash('sha256', $expiredToken),
            'expires_at' => now()->subDay(),
        ]);

        $this->getJson("/api/v1/enterprise/invitations/{$expiredToken}")->assertStatus(410);

        $realtor = User::factory()->create(['email' => 'existing@example.test']);
        $existingToken = Str::random(64);
        EnterpriseInvitation::create([
            'enterprise_id' => $enterprise->id,
            'email' => $realtor->email,
            'role' => 'employee',
            'team_id' => $team->id,
            'invited_by' => $admin->id,
            'token_hash' => hash('sha256', $existingToken),
            'expires_at' => now()->addDays(7),
        ]);

        $this->postJson("/api/v1/enterprise/invitations/{$existingToken}/accept", [
            'name' => 'Existing Realtor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('enterprise_memberships', [
            'enterprise_id' => $enterprise->id,
            'user_id' => $realtor->id,
        ]);
    }

    public function test_enterprise_plan_seat_limit_blocks_extra_managers(): void
    {
        $plan = $this->makePlan([
            'key' => 'enterprise-seat-limit',
            'max_managers' => 0,
        ]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/invitations', [
            'email' => 'manager@example.test',
            'role' => 'manager',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('limit');
    }

    public function test_monthly_lead_limit_is_enforced_server_side(): void
    {
        $plan = $this->makePlan([
            'key' => 'enterprise-lead-limit',
            'max_monthly_leads' => 1,
        ]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/leads', [
            'name' => 'First Lead',
            'phone' => '9000000001',
        ])->assertCreated();

        $this->postJson('/api/v1/enterprise/leads', [
            'name' => 'Second Lead',
            'phone' => '9000000002',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('limit');

        $this->assertSame(1, Lead::where('enterprise_id', $enterprise->id)->count());
    }

    public function test_lead_duplicates_and_employee_self_assignment(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);

        Sanctum::actingAs($employee);

        $created = $this->postJson('/api/v1/enterprise/leads', [
            'name' => 'Walk In Lead',
            'phone' => '9111111111',
            'assigned_to' => $admin->id,
            'team_id' => null,
        ])->assertCreated()
            ->assertJsonPath('data.assigned_to', $employee->id)
            ->assertJsonPath('data.team_id', $team->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'lead_assignment',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/leads', [
            'name' => 'Duplicate Lead',
            'phone' => '9111111111',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('duplicate');

        $this->postJson('/api/v1/enterprise/leads', [
            'name' => 'Duplicate Lead',
            'phone' => '9111111111',
            'allow_duplicate' => true,
        ])->assertCreated();
    }

    public function test_pipeline_stage_rules_are_enforced(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $lead = $this->makeLead($enterprise);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'contacted',
        ])->assertStatus(422)->assertJsonValidationErrors('note');

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'contacted',
            'note' => 'Spoke to customer on the phone.',
        ])->assertOk()
            ->assertJsonPath('data.stage', 'contacted');

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'qualified',
            'note' => 'Interested in a 2BHK.',
        ])->assertStatus(422)->assertJsonValidationErrors('qualification');

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'qualified',
            'note' => 'Interested in a 2BHK.',
            'budget_min' => 5000000,
        ])->assertOk()
            ->assertJsonPath('data.stage', 'qualified');

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'won',
        ])->assertStatus(422)->assertJsonValidationErrors('deal_value');

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'won',
            'deal_value' => 6200000,
        ])->assertOk()
            ->assertJsonPath('data.stage', 'won');

        $this->assertNotNull($lead->fresh()->won_at);

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'negotiation',
        ])->assertStatus(422)->assertJsonValidationErrors('stage');
    }

    public function test_admin_can_delete_a_lead_but_employees_cannot(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $lead = $this->makeLead($enterprise, ['name' => 'Delete Enterprise Lead']);
        [$employee] = $this->makeMember($enterprise, 'employee');

        Sanctum::actingAs($employee);

        $this->deleteJson("/api/v1/enterprise/leads/{$lead->id}")->assertForbidden();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/enterprise/leads/{$lead->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('enterprise_audit_logs', [
            'enterprise_id' => $enterprise->id,
            'action' => 'lead.deleted',
            'subject_id' => $lead->id,
        ]);
    }

    public function test_manager_can_only_assign_leads_within_own_team(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$managerA] = $this->makeMember($enterprise, 'manager');
        [$managerB] = $this->makeMember($enterprise, 'manager');
        $teamA = $this->makeTeam($enterprise, $managerA, 'Team A');
        $teamB = $this->makeTeam($enterprise, $managerB, 'Team B');
        [$employeeA] = $this->makeMember($enterprise, 'employee', $teamA);
        [$employeeB] = $this->makeMember($enterprise, 'employee', $teamB);

        $lead = $this->makeLead($enterprise, ['team_id' => $teamA->id]);

        Sanctum::actingAs($managerA);

        $this->postJson("/api/v1/enterprise/leads/{$lead->id}/assign", [
            'assigned_to' => $employeeB->id,
        ])->assertStatus(422)->assertJsonValidationErrors('assigned_to');

        $this->postJson("/api/v1/enterprise/leads/{$lead->id}/assign", [
            'assigned_to' => $employeeB->id,
            'team_id' => $teamB->id,
        ])->assertStatus(422)->assertJsonValidationErrors('team_id');

        $this->postJson("/api/v1/enterprise/leads/{$lead->id}/assign", [
            'assigned_to' => $employeeA->id,
        ])->assertOk()
            ->assertJsonPath('data.assigned_to', $employeeA->id);

        $secondLead = $this->makeLead($enterprise, ['team_id' => $teamA->id]);

        $this->postJson("/api/v1/enterprise/leads/{$secondLead->id}/round-robin")
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $employeeA->id);

        Sanctum::actingAs($managerB);
        $this->getJson("/api/v1/enterprise/leads/{$lead->id}")->assertStatus(403);
    }

    public function test_admin_can_distribute_leads_to_a_team_with_round_robin(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employeeA] = $this->makeMember($enterprise, 'employee', $team);
        [$employeeB] = $this->makeMember($enterprise, 'employee', $team);

        $leads = collect(range(1, 4))
            ->map(fn ($index) => $this->makeLead($enterprise, ['name' => "Bulk Lead {$index}"]));

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => $leads->pluck('id')->all(),
            'team_id' => $team->id,
            'mode' => 'round_robin',
        ])->assertOk()
            ->assertJsonPath('data.assigned_count', 4)
            ->json('data');

        $this->assertCount(4, $response['assignments']);

        foreach ($leads as $lead) {
            $lead->refresh();
            $this->assertSame($team->id, $lead->team_id);
            $this->assertNotNull($lead->assigned_at);
            $this->assertContains($lead->assigned_to, [$employeeA->id, $employeeB->id]);
        }

        $counts = $leads
            ->map(fn (Lead $lead) => $lead->fresh()->assigned_to)
            ->countBy();

        $this->assertSame(2, $counts[$employeeA->id]);
        $this->assertSame(2, $counts[$employeeB->id]);

        $this->assertDatabaseHas('enterprise_audit_logs', [
            'enterprise_id' => $enterprise->id,
            'user_id' => $admin->id,
            'action' => 'leads.distributed',
        ]);
    }

    public function test_manual_distribution_is_scoped_and_rejects_cross_tenant_leads(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$managerA] = $this->makeMember($enterprise, 'manager');
        [$managerB] = $this->makeMember($enterprise, 'manager');
        $teamA = $this->makeTeam($enterprise, $managerA, 'Team A');
        $teamB = $this->makeTeam($enterprise, $managerB, 'Team B');
        [$employeeA] = $this->makeMember($enterprise, 'employee', $teamA);
        [$employeeB] = $this->makeMember($enterprise, 'employee', $teamB);

        $leadA = $this->makeLead($enterprise, ['team_id' => $teamA->id]);
        $leadB = $this->makeLead($enterprise, ['team_id' => $teamB->id]);

        [$otherEnterprise] = $this->makeEnterprise(null, null, 'Other Realty');
        $foreignLead = $this->makeLead($otherEnterprise);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadA->id],
            'team_id' => $teamA->id,
            'mode' => 'manual',
            'assigned_to' => $employeeB->id,
        ])->assertStatus(422)->assertJsonValidationErrors('assigned_to');

        $this->assertNull($leadA->fresh()->assigned_to);

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadA->id, $foreignLead->id],
            'team_id' => $teamA->id,
            'mode' => 'manual',
            'assigned_to' => $employeeA->id,
        ])->assertStatus(422)->assertJsonValidationErrors('lead_ids');

        $this->assertNull($leadA->fresh()->assigned_to);

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadA->id],
            'team_id' => $teamA->id,
            'mode' => 'manual',
            'assigned_to' => $employeeA->id,
        ])->assertOk()->assertJsonPath('data.assigned_count', 1);

        $this->assertSame($employeeA->id, $leadA->fresh()->assigned_to);

        Sanctum::actingAs($managerA);

        $leadC = $this->makeLead($enterprise, ['team_id' => $teamA->id]);

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadC->id],
            'team_id' => $teamB->id,
            'mode' => 'round_robin',
        ])->assertStatus(422)->assertJsonValidationErrors('team_id');

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadB->id],
            'team_id' => $teamA->id,
            'mode' => 'round_robin',
        ])->assertStatus(422)->assertJsonValidationErrors('lead_ids');

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadC->id],
            'team_id' => $teamA->id,
            'mode' => 'round_robin',
        ])->assertOk()->assertJsonPath('data.assigned_count', 1);

        $this->assertNotNull($leadC->fresh()->assigned_to);

        Sanctum::actingAs($employeeA);

        $leadD = $this->makeLead($enterprise, ['team_id' => $teamA->id]);

        $this->postJson('/api/v1/enterprise/leads/distribute', [
            'lead_ids' => [$leadD->id],
            'team_id' => $teamA->id,
            'mode' => 'round_robin',
        ])->assertStatus(403);
    }

    public function test_tasks_are_scoped_and_employees_cannot_reassign(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employeeA] = $this->makeMember($enterprise, 'employee', $team);
        [$employeeB] = $this->makeMember($enterprise, 'employee', $team);

        Sanctum::actingAs($employeeA);

        $task = $this->postJson('/api/v1/enterprise/tasks', [
            'title' => 'Call the lead',
            'due_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.assignee.id', $employeeA->id)
            ->json('data');

        $this->postJson('/api/v1/enterprise/tasks', [
            'title' => 'Assign to someone else',
            'assigned_to' => $employeeB->id,
        ])->assertStatus(422)->assertJsonValidationErrors('assigned_to');

        $this->putJson("/api/v1/enterprise/tasks/{$task['id']}", [
            'team_id' => $team->id,
        ])->assertStatus(422)->assertJsonValidationErrors('team_id');

        $this->patchJson("/api/v1/enterprise/tasks/{$task['id']}/status", [
            'status' => 'completed',
            'completion_notes' => 'Done',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        Sanctum::actingAs($employeeB);
        $this->getJson("/api/v1/enterprise/tasks/{$task['id']}")->assertStatus(403);
    }

    public function test_site_visit_flow_updates_lead_and_creates_follow_up(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);
        $lead = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $employee->id,
        ]);
        [$project, $tower, $wing, $floor, $unit] = $this->makeInventory($enterprise, $admin);

        Sanctum::actingAs($employee);

        $visit = $this->postJson('/api/v1/enterprise/site-visits', [
            'lead_id' => $lead->id,
            'inventory_project_id' => $project->id,
            'inventory_unit_id' => $unit->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->json('data');

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'site_visit_scheduled',
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/site-visits/{$visit['id']}/status", [
            'status' => 'completed',
        ])->assertStatus(422)->assertJsonValidationErrors('outcome');

        $this->patchJson("/api/v1/enterprise/site-visits/{$visit['id']}/status", [
            'status' => 'completed',
            'customer_attended' => true,
            'outcome' => 'Customer liked the 2BHK unit.',
            'next_action' => 'Send the price sheet.',
            'create_follow_up' => true,
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('tasks', [
            'enterprise_id' => $enterprise->id,
            'lead_id' => $lead->id,
            'assigned_to' => $employee->id,
            'status' => 'pending',
        ]);

        $this->patchJson("/api/v1/enterprise/leads/{$lead->id}/stage", [
            'stage' => 'site_visit_completed',
        ])->assertOk()
            ->assertJsonPath('data.stage', 'site_visit_completed');
    }

    public function test_admin_can_schedule_a_site_visit_for_themselves(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $lead = $this->makeLead($enterprise, ['assigned_to' => $admin->id]);
        [$project] = $this->makeInventory($enterprise, $admin);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/site-visits', [
            'lead_id' => $lead->id,
            'inventory_project_id' => $project->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertDatabaseHas('site_visits', [
            'enterprise_id' => $enterprise->id,
            'lead_id' => $lead->id,
            'assigned_to' => $admin->id,
        ]);
    }

    public function test_inventory_status_transitions_and_tenant_guard(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);
        [, , , , $unit] = $this->makeInventory($enterprise, $admin);

        [$otherEnterprise, $otherAdmin] = $this->makeEnterprise(name: 'Other Realty');
        [, , , , $otherUnit] = $this->makeInventory($otherEnterprise, $otherAdmin);

        Sanctum::actingAs($employee);
        $this->patchJson("/api/v1/enterprise/inventory/units/{$unit->id}/status", [
            'availability_status' => 'hold',
        ])->assertStatus(403);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/v1/enterprise/inventory/units/{$otherUnit->id}/status", [
            'availability_status' => 'hold',
        ])->assertNotFound();

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unit->id}/status", [
            'availability_status' => 'hold',
        ])->assertOk()->assertJsonPath('data.availability_status', 'hold');

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unit->id}/status", [
            'availability_status' => 'booked',
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unit->id}/status", [
            'availability_status' => 'sold',
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unit->id}/status", [
            'availability_status' => 'available',
        ])->assertStatus(422)->assertJsonValidationErrors('availability_status');

        $this->assertDatabaseCount('inventory_unit_status_logs', 3);
    }

    public function test_sales_targets_are_role_scoped_and_duplicates_are_blocked(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$manager] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $manager);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);
        $period = now()->format('Y-m');

        Sanctum::actingAs($employee);
        $this->postJson('/api/v1/enterprise/targets', [
            'period_month' => $period,
            'metric' => 'leads_assigned',
            'target_value' => 10,
            'team_id' => $team->id,
        ])->assertStatus(403);

        Sanctum::actingAs($manager);
        $payload = [
            'period_month' => $period,
            'metric' => 'leads_assigned',
            'target_value' => 10,
            'team_id' => $team->id,
        ];

        $this->postJson('/api/v1/enterprise/targets', $payload)
            ->assertCreated()
            ->assertJsonPath('data.target_value', 10);

        $this->postJson('/api/v1/enterprise/targets', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('metric');

        $this->getJson('/api/v1/enterprise/targets')->assertOk();
    }

    public function test_reports_export_is_csv_safe_and_audit_log_is_admin_only(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();

        Sanctum::actingAs($admin);

        $created = $this->postJson('/api/v1/enterprise/leads', [
            'name' => '=SUM(1,1)',
            'email' => 'formula@lead.test',
        ])->assertCreated()->json('data');

        $this->getJson('/api/v1/enterprise/reports')
            ->assertOk()
            ->assertJsonPath('data.summary.total_leads', 1);

        $export = $this->get('/api/v1/enterprise/reports/export?type=leads');
        $export->assertOk();
        $this->assertStringContainsString('text/csv', $export->headers->get('content-type'));

        $csv = $export->streamedContent();
        $this->assertStringContainsString('Lead ID', $csv);
        $this->assertStringContainsString("'=SUM(1,1)", $csv);

        $this->getJson('/api/v1/enterprise/audit-logs')
            ->assertOk()
            ->assertJsonFragment(['action' => 'lead.created']);

        $this->assertSame($created['id'], Lead::where('name', '=SUM(1,1)')->value('id'));
    }

    public function test_campaign_is_tenant_scoped(): void
    {
        [$enterpriseA, $adminA] = $this->makeEnterprise(name: 'Campaign Alpha');
        [$enterpriseB, $adminB] = $this->makeEnterprise(name: 'Campaign Beta');

        $campaignA = Campaign::create([
            'user_id' => $adminA->id,
            'enterprise_id' => $enterpriseA->id,
            'name' => 'Alpha Campaign',
            'status' => 'active',
        ]);
        $campaignB = Campaign::create([
            'user_id' => $adminB->id,
            'enterprise_id' => $enterpriseB->id,
            'name' => 'Beta Campaign',
            'status' => 'active',
        ]);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/v1/enterprise/campaigns/{$campaignA->id}")->assertOk();
        $this->getJson("/api/v1/enterprise/campaigns/{$campaignB->id}")->assertNotFound();

        $this->getJson('/api/v1/enterprise/campaigns')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Alpha Campaign'])
            ->assertJsonMissing(['name' => 'Beta Campaign']);
    }
}
