<?php

namespace Tests\Feature\Api;

use App\Models\EnterpriseInvitation;
use App\Models\EnterpriseMembership;
use App\Models\SalesTarget;
use App\Models\SiteVisit;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class EnterpriseHierarchyTest extends TestCase
{
    use BuildsEnterpriseWorkspace;
    use RefreshDatabase;

    public function test_admin_can_build_a_multi_level_reporting_chain_and_see_the_nested_tree(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');
        [, $employeeOne] = $this->makeMember($enterprise, 'employee');
        [, $employeeTwo] = $this->makeMember($enterprise, 'employee');
        [, $employeeThree] = $this->makeMember($enterprise, 'employee');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/enterprise/users/{$manager->id}/manager", [
            'reports_to_membership_id' => $adminMembership->id,
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/users/{$employeeOne->id}/manager", [
            'reports_to_membership_id' => $manager->id,
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/users/{$employeeTwo->id}/manager", [
            'reports_to_membership_id' => $employeeOne->id,
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/users/{$employeeThree->id}/manager", [
            'reports_to_membership_id' => $employeeTwo->id,
        ])->assertOk();

        $this->getJson('/api/v1/enterprise/organization/tree')
            ->assertOk()
            ->assertJsonPath('data.scope', 'all')
            ->assertJsonPath('data.can_manage_hierarchy', true)
            ->assertJsonPath('data.roots.0.membership_id', $adminMembership->id)
            ->assertJsonPath('data.roots.0.children.0.membership_id', $manager->id)
            ->assertJsonPath('data.roots.0.children.0.children.0.membership_id', $employeeOne->id)
            ->assertJsonPath('data.roots.0.children.0.children.0.children.0.membership_id', $employeeTwo->id)
            ->assertJsonPath('data.roots.0.children.0.children.0.children.0.children.0.membership_id', $employeeThree->id)
            ->assertJsonPath('data.roots.0.children.0.children.0.is_lead', true)
            ->assertJsonPath('data.roots.0.children.0.children.0.subtree_size', 3)
            ->assertJsonPath('data.roots.0.subtree_size', 5);
    }

    public function test_admin_can_build_a_direct_employee_chain(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $employeeOne] = $this->makeMember($enterprise, 'employee');
        [, $employeeTwo] = $this->makeMember($enterprise, 'employee');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/enterprise/users/{$employeeOne->id}/manager", [
            'reports_to_membership_id' => $adminMembership->id,
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/users/{$employeeTwo->id}/manager", [
            'reports_to_membership_id' => $employeeOne->id,
        ])->assertOk();

        $this->getJson("/api/v1/enterprise/users/{$employeeOne->id}")
            ->assertOk()
            ->assertJsonPath('data.membership.reports_to_membership_id', $adminMembership->id)
            ->assertJsonPath('data.membership.is_lead', true)
            ->assertJsonPath('data.hierarchy.direct_reports_count', 1);

        $this->getJson('/api/v1/enterprise/reference-data')
            ->assertOk()
            ->assertJsonPath('data.permissions.can_view_hierarchy', true);
    }

    public function test_self_reference_and_cycles_are_rejected(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');
        [, $employee] = $this->makeMember($enterprise, 'employee');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/enterprise/users/{$manager->id}/manager", [
            'reports_to_membership_id' => $adminMembership->id,
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $manager->id,
        ])->assertOk();

        $this->patchJson("/api/v1/enterprise/users/{$manager->id}/manager", [
            'reports_to_membership_id' => $manager->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_to_membership_id');

        $this->patchJson("/api/v1/enterprise/users/{$manager->id}/manager", [
            'reports_to_membership_id' => $employee->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_to_membership_id');

        $this->patchJson("/api/v1/enterprise/users/{$adminMembership->id}/manager", [
            'reports_to_membership_id' => $manager->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_to_membership_id');
    }

    public function test_cross_tenant_manager_and_member_are_rejected_with_404_and_422(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$otherEnterprise] = $this->makeEnterprise(null, null, 'Other Realty');

        [, $foreignManager] = $this->makeMember($otherEnterprise, 'manager');
        [, $ownEmployee] = $this->makeMember($enterprise, 'employee');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/enterprise/users/{$ownEmployee->id}/manager", [
            'reports_to_membership_id' => $foreignManager->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_to_membership_id');

        $this->patchJson("/api/v1/enterprise/users/{$foreignManager->id}/manager", [
            'reports_to_membership_id' => null,
        ])->assertStatus(404);

        $this->getJson("/api/v1/enterprise/users/{$foreignManager->id}")
            ->assertStatus(404);
    }

    public function test_only_admins_can_change_reporting_lines(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');
        [, $employee] = $this->makeMember($enterprise, 'employee');

        Sanctum::actingAs($manager->user);

        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $manager->id,
        ])->assertStatus(403);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $manager->id,
        ])->assertOk();

        Sanctum::actingAs($employee->user);
        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $adminMembership->id,
        ])->assertStatus(403);
    }

    public function test_suspended_and_removed_members_cannot_be_selected_as_manager(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $suspendedManager] = $this->makeMember($enterprise, 'manager');
        [, $removedManager] = $this->makeMember($enterprise, 'manager');
        [, $employee] = $this->makeMember($enterprise, 'employee');

        $suspendedManager->update(['status' => 'suspended', 'suspended_at' => now()]);
        $removedManager->update(['status' => 'removed', 'removed_at' => now()]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $suspendedManager->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_to_membership_id');

        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $removedManager->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_to_membership_id');

        $this->patchJson("/api/v1/enterprise/users/{$employee->id}/manager", [
            'reports_to_membership_id' => $adminMembership->id,
        ])->assertOk();
    }

    public function test_manager_scope_is_union_of_downline_and_managed_team_without_other_teams(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $manager->user, 'Managed Team');
        [$teamEmployee, $teamMembership] = $this->makeMember($enterprise, 'employee', $team);
        [$downlineEmployee, $downlineMembership] = $this->makeMember($enterprise, 'employee');
        [$outsiderUser, $outsiderMembership] = $this->makeMember($enterprise, 'employee');

        $this->setManager($manager, $adminMembership->id);
        $this->setManager($downlineMembership, $manager->id);

        $leadManaged = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $teamEmployee->id,
            'assigned_manager_id' => $manager->user_id,
        ]);
        $leadDownline = $this->makeLead($enterprise, ['assigned_to' => $downlineEmployee->id]);
        $leadOutsider = $this->makeLead($enterprise, ['assigned_to' => $outsiderUser->id]);

        Sanctum::actingAs($manager->user);

        $visible = collect($this->getJson('/api/v1/enterprise/leads?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($leadManaged->id, $visible);
        $this->assertContains($leadDownline->id, $visible);
        $this->assertNotContains($leadOutsider->id, $visible);

        $members = collect($this->getJson('/api/v1/enterprise/users?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($manager->id, $members);
        $this->assertContains($teamMembership->id, $members);
        $this->assertContains($downlineMembership->id, $members);
        $this->assertNotContains($outsiderMembership->id, $members);

        $this->getJson('/api/v1/enterprise/organization/tree')
            ->assertOk()
            ->assertJsonPath('data.scope', 'subtree')
            ->assertJsonPath('data.roots.0.membership_id', $manager->id);
    }

    public function test_lead_employee_sees_only_the_subtree_and_plain_employee_only_self(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $leadEmployee] = $this->makeMember($enterprise, 'employee');
        [, $report] = $this->makeMember($enterprise, 'employee');
        [$plainUser, $plain] = $this->makeMember($enterprise, 'employee');
        [$outsiderUser] = $this->makeMember($enterprise, 'employee');

        $this->setManager($leadEmployee, $adminMembership->id);
        $this->setManager($report, $leadEmployee->id);

        $leadOwn = $this->makeLead($enterprise, ['assigned_to' => $leadEmployee->user_id]);
        $leadReport = $this->makeLead($enterprise, ['assigned_to' => $report->user_id]);
        $leadPlain = $this->makeLead($enterprise, ['assigned_to' => $plainUser->id]);
        $leadOutsider = $this->makeLead($enterprise, ['assigned_to' => $outsiderUser->id]);

        Sanctum::actingAs($leadEmployee->user);

        $visible = collect($this->getJson('/api/v1/enterprise/leads?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$leadOwn->id, $leadReport->id], $visible);

        $this->getJson('/api/v1/enterprise/organization/tree')
            ->assertOk()
            ->assertJsonPath('data.scope', 'subtree')
            ->assertJsonPath('data.roots.0.membership_id', $leadEmployee->id)
            ->assertJsonPath('data.roots.0.children.0.membership_id', $report->id);

        Sanctum::actingAs($plainUser);

        $plainVisible = collect($this->getJson('/api/v1/enterprise/leads?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$leadPlain->id], $plainVisible);

        $this->getJson('/api/v1/enterprise/organization/tree')
            ->assertOk()
            ->assertJsonPath('data.scope', 'self')
            ->assertJsonPath('data.roots.0.membership_id', $plain->id)
            ->assertJsonCount(0, 'data.roots.0.children');

        $this->getJson('/api/v1/enterprise/reference-data')
            ->assertOk()
            ->assertJsonPath('data.permissions.can_view_hierarchy', false)
            ->assertJsonPath('data.permissions.can_assign_leads', false);
    }

    public function test_lead_employee_can_assign_only_within_the_subtree(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $leadEmployee] = $this->makeMember($enterprise, 'employee');
        [$reportUser, $report] = $this->makeMember($enterprise, 'employee');
        [$outsiderUser] = $this->makeMember($enterprise, 'employee');

        $this->setManager($leadEmployee, $adminMembership->id);
        $this->setManager($report, $leadEmployee->id);

        $lead = $this->makeLead($enterprise, ['assigned_to' => $leadEmployee->user_id]);

        Sanctum::actingAs($leadEmployee->user);

        $this->postJson("/api/v1/enterprise/leads/{$lead->id}/assign", [
            'assigned_to' => $reportUser->id,
        ])->assertOk()
            ->assertJsonPath('data.assigned_to', $reportUser->id);

        $this->postJson("/api/v1/enterprise/leads/{$lead->id}/assign", [
            'assigned_to' => $outsiderUser->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('assigned_to');
    }

    public function test_task_visit_and_target_visibility_follow_the_hierarchy(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $leadEmployee] = $this->makeMember($enterprise, 'employee');
        [, $report] = $this->makeMember($enterprise, 'employee');
        [$outsiderUser] = $this->makeMember($enterprise, 'employee');

        $this->setManager($leadEmployee, $adminMembership->id);
        $this->setManager($report, $leadEmployee->id);

        $reportLead = $this->makeLead($enterprise, ['assigned_to' => $report->user_id]);
        $ownTask = Task::create([
            'enterprise_id' => $enterprise->id,
            'title' => 'Own task',
            'assigned_to' => $leadEmployee->user_id,
            'created_by' => $leadEmployee->user_id,
            'status' => 'pending',
            'priority' => 'medium',
        ]);
        $reportTask = Task::create([
            'enterprise_id' => $enterprise->id,
            'title' => 'Report task',
            'assigned_to' => $report->user_id,
            'created_by' => $leadEmployee->user_id,
            'status' => 'pending',
            'priority' => 'medium',
        ]);
        $outsiderTask = Task::create([
            'enterprise_id' => $enterprise->id,
            'title' => 'Outsider task',
            'assigned_to' => $outsiderUser->id,
            'created_by' => $outsiderUser->id,
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $visitReport = SiteVisit::create([
            'enterprise_id' => $enterprise->id,
            'lead_id' => $reportLead->id,
            'assigned_to' => $report->user_id,
            'created_by' => $leadEmployee->user_id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);
        $visitOutsider = SiteVisit::create([
            'enterprise_id' => $enterprise->id,
            'lead_id' => $this->makeLead($enterprise, ['assigned_to' => $outsiderUser->id])->id,
            'assigned_to' => $outsiderUser->id,
            'created_by' => $outsiderUser->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $ownTarget = SalesTarget::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $leadEmployee->user_id,
            'period_month' => now()->startOfMonth(),
            'metric' => 'leads_assigned',
            'target_value' => 10,
            'created_by' => $admin->id,
        ]);
        $reportTarget = SalesTarget::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $report->user_id,
            'period_month' => now()->startOfMonth(),
            'metric' => 'leads_assigned',
            'target_value' => 5,
            'created_by' => $admin->id,
        ]);
        $outsiderTarget = SalesTarget::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $outsiderUser->id,
            'period_month' => now()->startOfMonth(),
            'metric' => 'leads_assigned',
            'target_value' => 8,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($leadEmployee->user);

        $taskIds = collect($this->getJson('/api/v1/enterprise/tasks?per_page=50')->assertOk()->json('data'))
            ->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$ownTask->id, $reportTask->id], $taskIds);
        $this->assertNotContains($outsiderTask->id, $taskIds);

        $visitIds = collect($this->getJson('/api/v1/enterprise/site-visits?per_page=50')->assertOk()->json('data'))
            ->pluck('id')->all();
        $this->assertContains($visitReport->id, $visitIds);
        $this->assertNotContains($visitOutsider->id, $visitIds);

        $targetIds = collect($this->getJson('/api/v1/enterprise/targets?per_page=50')->assertOk()->json('data.targets'))
            ->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$ownTarget->id, $reportTarget->id], $targetIds);
        $this->assertNotContains($outsiderTarget->id, $targetIds);
    }

    public function test_removing_a_manager_reparents_direct_reports_and_audits_the_change(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [$managerUser, $manager] = $this->makeMember($enterprise, 'manager');
        [, $firstReport] = $this->makeMember($enterprise, 'employee');
        [, $secondReport] = $this->makeMember($enterprise, 'employee');

        $this->setManager($manager, $adminMembership->id);
        $this->setManager($firstReport, $manager->id);
        $this->setManager($secondReport, $manager->id);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/enterprise/users/{$manager->id}/remove", [
            'lead_action' => 'unassign',
            'reports_action' => 'reparent_to_admin',
        ])->assertOk()
            ->assertJsonPath('data.reports_moved', 2);

        $this->assertEquals($adminMembership->id, $firstReport->fresh()->reports_to_membership_id);
        $this->assertEquals($adminMembership->id, $secondReport->fresh()->reports_to_membership_id);
        $this->assertDatabaseHas('enterprise_audit_logs', [
            'enterprise_id' => $enterprise->id,
            'action' => 'member.removed',
        ]);
    }

    public function test_removing_a_member_with_reports_requires_a_reports_action(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');
        [, $report] = $this->makeMember($enterprise, 'employee');

        $this->setManager($manager, $adminMembership->id);
        $this->setManager($report, $manager->id);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/enterprise/users/{$manager->id}/remove", [
            'lead_action' => 'unassign',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('reports_action');
    }

    public function test_direct_add_and_invitation_persist_the_selected_manager_and_acceptance_falls_back_to_admin(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/users', [
            'name' => 'Direct Report',
            'email' => 'direct.report@example.test',
            'password' => 'Password#2026',
            'password_confirmation' => 'Password#2026',
            'role' => 'employee',
            'reports_to_membership_id' => $manager->id,
        ])->assertCreated()
            ->assertJsonPath('data.reports_to_membership_id', $manager->id);

        $token = str_repeat('a', 64);
        $invitation = EnterpriseInvitation::create([
            'enterprise_id' => $enterprise->id,
            'email' => 'invited.report@example.test',
            'role' => 'employee',
            'reports_to_membership_id' => $manager->id,
            'invited_by' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        $this->getJson("/api/v1/enterprise/invitations/{$token}")
            ->assertOk()
            ->assertJsonPath('data.invitation.reports_to_membership_id', $manager->id);

        // The intended manager leaves after the invitation is sent.
        $manager->update(['status' => 'suspended', 'suspended_at' => now()]);

        $this->postJson("/api/v1/enterprise/invitations/{$token}/accept", [
            'name' => 'Invited Report',
            'password' => 'Password#2026',
            'password_confirmation' => 'Password#2026',
        ])->assertCreated()
            ->assertJsonPath('data.membership.reports_to_membership_id', $adminMembership->id);
    }

    public function test_me_and_reference_data_expose_hierarchy_flags(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [, $manager] = $this->makeMember($enterprise, 'manager');
        [, $report] = $this->makeMember($enterprise, 'employee');

        $this->setManager($manager, $adminMembership->id);
        $this->setManager($report, $manager->id);

        Sanctum::actingAs($manager->user);

        $this->getJson('/api/v1/enterprise/me')
            ->assertOk()
            ->assertJsonPath('data.hierarchy.scope', 'subtree')
            ->assertJsonPath('data.hierarchy.can_manage_hierarchy', false)
            ->assertJsonPath('data.hierarchy.has_reports', true)
            ->assertJsonPath('data.hierarchy.reports_to.membership_id', $adminMembership->id)
            ->assertJsonPath('data.hierarchy.direct_reports_count', 1);

        $this->getJson('/api/v1/enterprise/reference-data')
            ->assertOk()
            ->assertJsonPath('data.permissions.can_manage_hierarchy', false)
            ->assertJsonPath('data.permissions.has_reports', true)
            ->assertJsonPath('data.hierarchy.reports_to.membership_id', $adminMembership->id);
    }

    public function test_backfill_command_builds_reporting_lines_from_teams_and_admins(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        [$managerUser, $manager] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $managerUser, 'Legacy Team');
        [, $employee] = $this->makeMember($enterprise, 'employee', $team);

        $this->artisan('enterprise:backfill-reporting-lines', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull($manager->fresh()->reports_to_membership_id);
        $this->assertNull($employee->fresh()->reports_to_membership_id);

        $this->artisan('enterprise:backfill-reporting-lines')
            ->assertExitCode(0);

        $this->assertEquals($adminMembership->id, $manager->fresh()->reports_to_membership_id);
        $this->assertEquals($manager->id, $employee->fresh()->reports_to_membership_id);
        $this->assertNull($adminMembership->fresh()->reports_to_membership_id);
    }

    private function setManager(EnterpriseMembership $member, ?int $managerId): void
    {
        $member->forceFill(['reports_to_membership_id' => $managerId])->save();
    }
}
