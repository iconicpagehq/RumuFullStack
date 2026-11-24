<?php

namespace Tests\Feature\Api;

use App\Jobs\SendAdminBroadcastJob;
use App\Models\AdminBroadcast;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class AdminEnterpriseOversightTest extends TestCase
{
    use BuildsEnterpriseWorkspace;
    use RefreshDatabase;

    public function test_admin_can_list_inspect_suspend_and_resume_enterprises(): void
    {
        [$enterprise, $enterpriseAdmin] = $this->makeEnterprise(name: 'Oversight Realty');
        $this->makeTeam($enterprise, $enterpriseAdmin);
        $this->makeLead($enterprise, ['assigned_to' => $enterpriseAdmin->id]);

        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/enterprises?search=Oversight')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Oversight Realty')
            ->assertJsonPath('data.0.active_members', 1)
            ->assertJsonPath('data.0.leads', 1);

        $this->getJson("/api/v1/admin/enterprises/{$enterprise->id}")
            ->assertOk()
            ->assertJsonPath('data.enterprise.name', 'Oversight Realty')
            ->assertJsonPath('data.counts.members', 1)
            ->assertJsonPath('data.members.0.role', 'admin');

        $this->postJson("/api/v1/admin/enterprises/{$enterprise->id}/suspend")
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'enterprise.suspended']);

        Sanctum::actingAs($enterpriseAdmin);
        $this->getJson('/api/v1/enterprise/me')->assertStatus(403);
        $this->postJson('/api/v1/auth/login', [
            'email' => $enterpriseAdmin->email,
            'password' => 'password123',
        ])->assertStatus(403);

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/admin/enterprises/{$enterprise->id}/suspend")->assertStatus(409);

        $this->postJson("/api/v1/admin/enterprises/{$enterprise->id}/resume")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'enterprise.resumed']);

        Sanctum::actingAs($enterpriseAdmin);
        $this->getJson('/api/v1/enterprise/me')->assertOk();
    }

    public function test_admin_leads_support_tenant_filters(): void
    {
        $plan = Plan::factory()->basic()->create();
        $realtor = User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Lead::factory()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Realtor Tenant Lead',
        ]);

        [$enterprise] = $this->makeEnterprise(name: 'Tenant Filter Realty');
        $this->makeLead($enterprise, ['name' => 'Enterprise Tenant Lead']);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $all = $this->getJson('/api/v1/admin/leads?per_page=50')->assertOk();
        $this->assertCount(2, $all->json('data'));

        $this->getJson('/api/v1/admin/leads?tenant=realtor&per_page=50')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Realtor Tenant Lead'])
            ->assertJsonMissing(['name' => 'Enterprise Tenant Lead']);

        $this->getJson('/api/v1/admin/leads?tenant=enterprise&per_page=50')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Enterprise Tenant Lead'])
            ->assertJsonMissing(['name' => 'Realtor Tenant Lead'])
            ->assertJsonPath('data.0.tenant', 'enterprise');

        $this->getJson("/api/v1/admin/leads?enterprise_id={$enterprise->id}&per_page=50")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_dashboard_and_analytics_include_enterprise_metrics(): void
    {
        [$enterprise] = $this->makeEnterprise(name: 'Metrics Realty');
        $this->makeMember($enterprise, 'manager');
        $this->makeLead($enterprise);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'enterprises_total',
                    'enterprises_active',
                    'enterprise_members',
                    'enterprise_leads_total',
                    'enterprise_leads_monthly',
                    'enterprise_plans',
                ],
            ])
            ->assertJsonPath('data.enterprises_total', 1)
            ->assertJsonPath('data.enterprises_active', 1)
            ->assertJsonPath('data.enterprise_members', 2)
            ->assertJsonPath('data.enterprise_leads_total', 1);

        $this->getJson('/api/v1/admin/analytics/enterprises')
            ->assertOk()
            ->assertJsonPath('data.kpis.total', 1)
            ->assertJsonPath('data.kpis.active', 1)
            ->assertJsonPath('data.kpis.members', 2)
            ->assertJsonPath('data.kpis.leads_total', 1)
            ->assertJsonStructure(['data' => ['months', 'new_enterprises_monthly', 'leads_monthly', 'plan_distribution']]);
    }

    public function test_admin_can_create_and_update_enterprise_plans_with_quotas_and_features(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/plans', [
            'name' => 'Enterprise Growth',
            'key' => 'enterprise-growth',
            'audience' => 'enterprise',
            'price' => 9999,
            'max_managers' => 3,
            'max_employees' => 30,
            'max_teams' => 4,
            'max_monthly_leads' => 900,
            'max_inventory_units' => 120,
            'advanced_reports' => true,
            'meta_integrations' => true,
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('data.audience', 'enterprise')
            ->assertJsonPath('data.meta_integrations', true);

        $plan = Plan::where('key', 'enterprise-growth')->firstOrFail();
        $this->assertSame(30, (int) $plan->max_employees);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'plan.created']);

        $this->putJson("/api/v1/admin/plans/{$plan->id}", [
            'name' => 'Enterprise Growth Plus',
            'audience' => 'enterprise',
            'max_managers' => 5,
            'max_employees' => 40,
            'advanced_reports' => false,
            'meta_integrations' => true,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Enterprise Growth Plus')
            ->assertJsonPath('data.advanced_reports', false);

        $plan->refresh();
        $this->assertSame(5, (int) $plan->max_managers);
        $this->assertFalse((bool) $plan->advanced_reports);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'plan.updated']);
    }

    public function test_broadcast_audiences_exclude_inactive_members_and_send_notifications(): void
    {
        Queue::fake();

        $activeRealtor = User::factory()->create(['role' => 'realtor']);
        User::factory()->create(['role' => 'realtor', 'subscription_suspended_at' => now()]);

        [$enterprise] = $this->makeEnterprise(name: 'Broadcast Realty');
        [$manager] = $this->makeMember($enterprise, 'manager');
        [$employee] = $this->makeMember($enterprise, 'employee');

        [$suspendedEnterprise] = $this->makeEnterprise(name: 'Suspended Realty');
        $suspendedEnterprise->update(['status' => 'suspended']);

        [$removedEmployee, $removedMembership] = $this->makeMember($enterprise, 'employee');
        $removedMembership->update(['status' => 'removed', 'removed_at' => now()]);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/broadcasts/preview', [
            'title' => 'Platform update',
            'message' => 'Maintenance window tonight.',
            'audience' => 'all_realtors',
        ])->assertOk()->assertJsonPath('data.recipients_count', 1);

        $this->postJson('/api/v1/admin/broadcasts/preview', [
            'title' => 'Platform update',
            'message' => 'Maintenance window tonight.',
            'audience' => 'all_enterprise_admins',
        ])->assertOk()->assertJsonPath('data.recipients_count', 1);

        $this->postJson('/api/v1/admin/broadcasts/preview', [
            'title' => 'Platform update',
            'message' => 'Maintenance window tonight.',
            'audience' => 'all_enterprise_members',
        ])->assertOk()->assertJsonPath('data.recipients_count', 3);

        $this->postJson('/api/v1/admin/broadcasts/preview', [
            'title' => 'Enterprise update',
            'message' => 'Your workspace has new features.',
            'audience' => 'enterprise',
            'enterprise_id' => $enterprise->id,
        ])->assertOk()->assertJsonPath('data.recipients_count', 3);

        $this->postJson('/api/v1/admin/broadcasts', [
            'title' => 'Platform update',
            'message' => 'Maintenance window tonight.',
            'audience' => 'all_enterprise_members',
        ])->assertCreated()
            ->assertJsonPath('data.recipients_count', 3)
            ->assertJsonPath('data.status', 'queued');

        Queue::assertPushed(SendAdminBroadcastJob::class);

        $broadcast = AdminBroadcast::firstOrFail();
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'broadcast.sent']);

        (new SendAdminBroadcastJob($broadcast))->handle();

        $broadcast->refresh();
        $this->assertSame('sent', $broadcast->status);
        $this->assertNotNull($broadcast->sent_at);
        $this->assertSame(3, $broadcast->recipients_count);

        foreach ([$enterprise->owner_id, $manager->id, $employee->id] as $userId) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $userId,
                'type' => 'admin_broadcast',
                'title' => 'Platform update',
            ]);
        }

        $this->assertDatabaseMissing('notifications', ['user_id' => $removedEmployee->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $suspendedEnterprise->owner_id]);

        $this->getJson('/api/v1/admin/broadcasts')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Platform update');
    }

    public function test_platform_audit_log_lists_and_filters_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/plans', [
            'name' => 'Audit Test Plan',
            'audience' => 'realtor',
            'is_active' => true,
        ])->assertCreated();

        $this->getJson('/api/v1/admin/audit-logs?action=plan.created')
            ->assertOk()
            ->assertJsonFragment(['action' => 'plan.created'])
            ->assertJsonPath('data.0.admin.email', $admin->email);

        $this->getJson('/api/v1/admin/audit-logs?search=Plan%20created')
            ->assertOk()
            ->assertJsonFragment(['description' => 'Plan created']);
    }
}
