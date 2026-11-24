<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\InventoryProject;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\RealtorSiteVisit;
use App\Models\RealtorTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class RealtorWorkspaceTest extends TestCase
{
    use BuildsEnterpriseWorkspace;
    use RefreshDatabase;

    private function makeRealtor(?Plan $plan = null): User
    {
        $plan ??= Plan::where('key', 'basic')->first() ?? Plan::factory()->basic()->create();

        return User::factory()->create([
            'role' => 'realtor',
            'plan_id' => $plan->id,
        ]);
    }

    private function makeOwnedLead(User $realtor, array $overrides = []): Lead
    {
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        return Lead::factory()->create(array_merge([
            'campaign_id' => $campaign->id,
        ], $overrides));
    }

    public function test_global_lead_board_is_scoped_to_the_realtor(): void
    {
        $realtor = $this->makeRealtor();
        $otherRealtor = $this->makeRealtor();

        $ownLead = $this->makeOwnedLead($realtor, [
            'name' => 'Own Pipeline Lead',
            'status' => 'qualified',
            'priority' => 'high',
            'phone' => '9876500001',
        ]);
        $this->makeOwnedLead($realtor, [
            'name' => 'Follow Up Lead',
            'status' => 'contacted',
            'phone' => '9876500002',
        ]);
        $this->makeOwnedLead($otherRealtor, ['name' => 'Other Realtor Lead']);

        [$enterprise] = $this->makeEnterprise(name: 'Leak Guard Realty');
        $enterpriseLead = Lead::create([
            'campaign_id' => $ownLead->campaign_id,
            'enterprise_id' => $enterprise->id,
            'name' => 'Enterprise Leak Lead',
            'phone' => '9876500003',
            'status' => 'new',
            'enterprise_stage' => 'new',
        ]);

        Sanctum::actingAs($realtor);

        $response = $this->getJson('/api/v1/realtor/leads?per_page=50')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Own Pipeline Lead'])
            ->assertJsonFragment(['name' => 'Follow Up Lead'])
            ->assertJsonMissing(['name' => 'Other Realtor Lead'])
            ->assertJsonMissing(['name' => 'Enterprise Leak Lead']);

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(1, (int) $response->json('meta.status_counts.qualified'));
        $this->assertSame(1, (int) $response->json('meta.status_counts.contacted'));

        $this->getJson('/api/v1/realtor/leads?status=qualified')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Own Pipeline Lead'])
            ->assertJsonMissing(['name' => 'Follow Up Lead']);

        $this->getJson('/api/v1/realtor/leads?priority=high')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Own Pipeline Lead');

        $this->getJson('/api/v1/realtor/leads?search=Follow')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Follow Up Lead'])
            ->assertJsonMissing(['name' => 'Own Pipeline Lead']);

        $this->getJson('/api/v1/realtor/leads?campaign_id=' . $ownLead->campaign_id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $ownLead->id);

        $this->assertNotSame($enterpriseLead->id, $ownLead->id);
    }

    public function test_realtor_csv_export_is_scoped_and_formula_safe(): void
    {
        $realtor = $this->makeRealtor();
        $otherRealtor = $this->makeRealtor();

        $this->makeOwnedLead($realtor, [
            'name' => '=SUM(1,1)',
            'email' => 'formula@realtor.test',
            'phone' => '9876500010',
        ]);
        $this->makeOwnedLead($otherRealtor, ['name' => 'Secret Other Lead']);

        Sanctum::actingAs($realtor);

        $response = $this->get('/api/v1/realtor/leads/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=SUM(1,1)", $csv);
        $this->assertStringNotContainsString('Secret Other Lead', $csv);
    }

    public function test_realtor_task_crud_is_personal_and_rejects_foreign_leads(): void
    {
        $realtor = $this->makeRealtor();
        $otherRealtor = $this->makeRealtor();

        $lead = $this->makeOwnedLead($realtor, ['name' => 'Task Lead']);
        $foreignLead = $this->makeOwnedLead($otherRealtor, ['name' => 'Foreign Lead']);

        Sanctum::actingAs($realtor);

        $created = $this->postJson('/api/v1/realtor/tasks', [
            'title' => 'Call about site visit',
            'description' => 'Confirm weekend slot',
            'priority' => 'high',
            'due_at' => now()->addDay()->toIso8601String(),
            'lead_id' => $lead->id,
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Call about site visit')
            ->assertJsonPath('data.status', 'pending')
            ->json('data');

        $this->getJson('/api/v1/realtor/tasks?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id'])
            ->assertJsonPath('meta.status_counts.pending', 1);

        $this->patchJson("/api/v1/realtor/tasks/{$created['id']}/status", [
            'status' => 'completed',
            'completion_notes' => 'Call completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertNotNull(RealtorTask::findOrFail($created['id'])->completed_at);

        $this->putJson("/api/v1/realtor/tasks/{$created['id']}", [
            'title' => 'Call about site visit (updated)',
            'priority' => 'urgent',
            'lead_id' => $lead->id,
        ])->assertOk()
            ->assertJsonPath('data.priority', 'urgent');

        $this->postJson('/api/v1/realtor/tasks', [
            'title' => 'Illegal foreign lead task',
            'priority' => 'medium',
            'lead_id' => $foreignLead->id,
        ])->assertNotFound();

        $otherTask = RealtorTask::create([
            'user_id' => $otherRealtor->id,
            'title' => 'Other realtor task',
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $this->getJson("/api/v1/realtor/tasks/{$otherTask->id}")->assertNotFound();

        $this->deleteJson("/api/v1/realtor/tasks/{$created['id']}")->assertOk();
        $this->assertSoftDeleted('realtor_tasks', ['id' => $created['id']]);
    }

    public function test_realtor_site_visit_crud_is_personal_and_rejects_foreign_records(): void
    {
        $realtor = $this->makeRealtor();
        $otherRealtor = $this->makeRealtor();

        $lead = $this->makeOwnedLead($realtor, ['name' => 'Visit Lead']);
        $foreignLead = $this->makeOwnedLead($otherRealtor, ['name' => 'Visit Foreign Lead']);

        $project = InventoryProject::create([
            'user_id' => $realtor->id,
            'name' => 'Visit Project',
            'slug' => 'visit-project-' . random_int(1000, 9999),
            'status' => 'active',
        ]);

        $foreignProject = InventoryProject::create([
            'user_id' => $otherRealtor->id,
            'name' => 'Foreign Project',
            'slug' => 'foreign-project-' . random_int(1000, 9999),
            'status' => 'active',
        ]);

        Sanctum::actingAs($realtor);

        $created = $this->postJson('/api/v1/realtor/site-visits', [
            'lead_id' => $lead->id,
            'inventory_project_id' => $project->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.project.id', $project->id)
            ->json('data');

        $this->getJson('/api/v1/realtor/site-visits')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id'])
            ->assertJsonPath('meta.upcoming_count', 1);

        $this->patchJson("/api/v1/realtor/site-visits/{$created['id']}/status", [
            'status' => 'completed',
            'customer_attended' => true,
            'outcome' => 'Customer requested a corner unit.',
            'next_action' => 'Share inventory options.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertNotNull(RealtorSiteVisit::findOrFail($created['id'])->completed_at);

        $this->postJson('/api/v1/realtor/site-visits', [
            'lead_id' => $foreignLead->id,
            'inventory_project_id' => $project->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertNotFound();

        $this->postJson('/api/v1/realtor/site-visits', [
            'lead_id' => $lead->id,
            'inventory_project_id' => $foreignProject->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertNotFound();

        $otherVisit = RealtorSiteVisit::create([
            'user_id' => $otherRealtor->id,
            'lead_id' => $foreignLead->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $this->getJson("/api/v1/realtor/site-visits/{$otherVisit->id}")->assertNotFound();

        $this->deleteJson("/api/v1/realtor/site-visits/{$created['id']}")->assertOk();
        $this->assertSoftDeleted('realtor_site_visits', ['id' => $created['id']]);
    }

    public function test_realtor_dashboard_includes_follow_ups_tasks_and_visits(): void
    {
        $realtor = $this->makeRealtor();
        $lead = $this->makeOwnedLead($realtor, [
            'name' => 'Follow Up Customer',
            'next_follow_up_at' => now()->addDay(),
        ]);

        RealtorTask::create([
            'user_id' => $realtor->id,
            'lead_id' => $lead->id,
            'title' => 'Upcoming task',
            'priority' => 'medium',
            'status' => 'pending',
            'due_at' => now()->addDay(),
        ]);

        RealtorTask::create([
            'user_id' => $realtor->id,
            'lead_id' => $lead->id,
            'title' => 'Overdue task',
            'priority' => 'high',
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);

        RealtorSiteVisit::create([
            'user_id' => $realtor->id,
            'lead_id' => $lead->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($realtor);

        $this->getJson('/api/v1/realtor/dashboard')
            ->assertOk()
            ->assertJsonCount(1, 'data.upcoming_tasks')
            ->assertJsonPath('data.upcoming_tasks.0.title', 'Upcoming task')
            ->assertJsonPath('data.overdue_tasks_count', 1)
            ->assertJsonCount(1, 'data.overdue_tasks')
            ->assertJsonCount(1, 'data.upcoming_site_visits')
            ->assertJsonCount(1, 'data.lead_follow_ups')
            ->assertJsonPath('data.lead_follow_ups.0.name', 'Follow Up Customer');
    }

    public function test_won_global_lead_appears_in_the_customer_directory(): void
    {
        $realtor = $this->makeRealtor();

        $this->makeOwnedLead($realtor, [
            'name' => 'Won Global Lead',
            'status' => 'won',
            'deal_value' => 7500000,
        ]);

        Sanctum::actingAs($realtor);

        $this->getJson('/api/v1/realtor/customers')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Won Global Lead'])
            ->assertJsonPath('data.stats.won', 1);
    }
}
