<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\User;
use App\Services\EnterpriseLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEnterpriseWorkspace;
use Tests\TestCase;

class EnterpriseParityTest extends TestCase
{
    use BuildsEnterpriseWorkspace;
    use RefreshDatabase;

    public function test_settings_and_billing_are_admin_only_while_export_is_admin_and_manager_only(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$manager] = $this->makeMember($enterprise, 'manager', $team);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);

        Sanctum::actingAs($employee);
        $this->getJson('/api/v1/enterprise/settings')->assertStatus(403);
        $this->getJson('/api/v1/enterprise/billing')->assertStatus(403);
        $this->getJson('/api/v1/enterprise/reports/export?type=leads')->assertStatus(403);
        $this->getJson('/api/v1/enterprise/me')->assertOk();

        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/enterprise/settings')->assertStatus(403);
        $this->getJson('/api/v1/enterprise/billing')->assertStatus(403);
        $this->get('/api/v1/enterprise/reports/export?type=leads')->assertOk();
        $this->assertStringContainsString(
            'text/csv',
            $this->get('/api/v1/enterprise/reports/export?type=leads')->headers->get('content-type')
        );

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/enterprise/settings')
            ->assertOk()
            ->assertJsonStructure(['data' => ['enterprise', 'membership', 'plan', 'usage', 'features']]);
        $this->getJson('/api/v1/enterprise/billing')
            ->assertOk()
            ->assertJsonStructure(['data' => ['plan', 'usage', 'seat_requests', 'features', 'invoices']]);
    }

    public function test_manager_can_set_employee_target_for_managed_team_only(): void
    {
        [$enterprise] = $this->makeEnterprise();
        [$manager] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $manager);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);

        $otherTeam = $this->makeTeam($enterprise, null, 'Other Team');
        [$otherEmployee] = $this->makeMember($enterprise, 'employee', $otherTeam);

        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/enterprise/targets', [
            'period_month' => now()->format('Y-m'),
            'metric' => 'leads_assigned',
            'target_value' => 25,
            'team_id' => $team->id,
            'user_id' => $employee->id,
        ])->assertCreated()
            ->assertJsonPath('data.target_value', 25);

        $this->postJson('/api/v1/enterprise/targets', [
            'period_month' => now()->format('Y-m'),
            'metric' => 'deals_won',
            'target_value' => 5,
            'user_id' => $otherEmployee->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('user_id');

        Sanctum::actingAs($employee);
        $this->postJson('/api/v1/enterprise/targets', [
            'period_month' => now()->format('Y-m'),
            'metric' => 'leads_assigned',
            'target_value' => 10,
            'team_id' => $team->id,
        ])->assertStatus(403);
    }

    public function test_employee_can_reserve_inventory_only_for_own_lead_and_manager_only_for_team_leads(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        [$manager] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $manager);
        [$employee] = $this->makeMember($enterprise, 'employee', $team);
        [$otherEmployee] = $this->makeMember($enterprise, 'employee', $team);

        $ownLead = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $employee->id,
            'assigned_manager_id' => $manager->id,
        ]);
        $foreignLead = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $otherEmployee->id,
            'assigned_manager_id' => $manager->id,
        ]);

        $otherTeam = $this->makeTeam($enterprise, null, 'Outside Team');
        $outsideLead = $this->makeLead($enterprise, ['team_id' => $otherTeam->id]);

        [, , , , $unitOwn] = $this->makeInventory($enterprise, $admin);
        [, , , , $unitForeign] = $this->makeInventory($enterprise, $admin);

        Sanctum::actingAs($employee);

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitOwn->id}/status", [
            'availability_status' => 'hold',
            'lead_id' => $ownLead->id,
        ])->assertOk()->assertJsonPath('data.availability_status', 'hold');

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitOwn->id}/status", [
            'availability_status' => 'available',
            'lead_id' => $ownLead->id,
        ])->assertStatus(403);

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitForeign->id}/status", [
            'availability_status' => 'hold',
            'lead_id' => $foreignLead->id,
        ])->assertStatus(403);

        Sanctum::actingAs($manager);

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitForeign->id}/status", [
            'availability_status' => 'hold',
        ])->assertStatus(422)->assertJsonValidationErrors('lead_id');

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitForeign->id}/status", [
            'availability_status' => 'hold',
            'lead_id' => $ownLead->id,
        ])->assertOk()->assertJsonPath('data.availability_status', 'hold');

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitForeign->id}/status", [
            'availability_status' => 'booked',
            'lead_id' => $outsideLead->id,
        ])->assertStatus(403);

        $this->patchJson("/api/v1/enterprise/inventory/units/{$unitForeign->id}/status", [
            'availability_status' => 'sold',
            'lead_id' => $ownLead->id,
        ])->assertStatus(403);
    }

    public function test_manager_can_transfer_leads_within_team_but_not_cross_team(): void
    {
        [$enterprise] = $this->makeEnterprise();
        [$manager] = $this->makeMember($enterprise, 'manager');
        $team = $this->makeTeam($enterprise, $manager);
        [$employee, $employeeMembership] = $this->makeMember($enterprise, 'employee', $team);
        [$destination] = $this->makeMember($enterprise, 'employee', $team);

        $otherTeam = $this->makeTeam($enterprise, null, 'Other Team');
        [$outsider, $outsiderMembership] = $this->makeMember($enterprise, 'employee', $otherTeam);

        $lead = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $employee->id,
            'assigned_manager_id' => $manager->id,
        ]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/enterprise/users/{$employeeMembership->id}/transfer", [
            'team_id' => $team->id,
            'lead_action' => 'transfer',
            'destination_user_id' => $destination->id,
        ])->assertOk();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_to' => $destination->id,
            'team_id' => $team->id,
        ]);

        $this->postJson("/api/v1/enterprise/users/{$outsiderMembership->id}/transfer", [
            'team_id' => $otherTeam->id,
            'lead_action' => 'unassign',
        ])->assertStatus(403);

        $this->postJson("/api/v1/enterprise/users/{$employeeMembership->id}/transfer", [
            'team_id' => $team->id,
            'lead_action' => 'transfer',
            'destination_user_id' => $outsider->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('destination_user_id');
    }

    public function test_admin_can_remove_member_with_lead_transfer_and_revoked_access(): void
    {
        [$enterprise, $admin, $adminMembership] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise, $admin);
        [$employee, $employeeMembership] = $this->makeMember($enterprise, 'employee', $team);
        [$destination] = $this->makeMember($enterprise, 'employee', $team);

        $openLead = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $employee->id,
        ]);
        $wonLead = $this->makeLead($enterprise, [
            'team_id' => $team->id,
            'assigned_to' => $employee->id,
            'enterprise_stage' => 'won',
            'status' => 'won',
        ]);

        $employee->createToken('old-device');
        $this->assertSame(1, $employee->tokens()->count());

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/enterprise/users/{$employeeMembership->id}/remove", [
            'lead_action' => 'transfer',
            'transfer_to' => $destination->id,
        ])->assertOk()
            ->assertJsonPath('data.leads_moved', 1);

        $employeeMembership->refresh();
        $this->assertSame('removed', $employeeMembership->status);
        $this->assertNotNull($employeeMembership->removed_at);

        $this->assertDatabaseHas('leads', [
            'id' => $openLead->id,
            'assigned_to' => $destination->id,
        ]);
        $this->assertDatabaseHas('leads', [
            'id' => $wonLead->id,
            'assigned_to' => $employee->id,
        ]);

        $this->assertSame(0, $employee->tokens()->count());
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'enterprise.member_removed']);
        $this->assertDatabaseHas('enterprise_audit_logs', ['action' => 'member.removed']);

        $this->getJson('/api/v1/enterprise/users')
            ->assertOk()
            ->assertJsonMissing(['email' => $employee->email]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $employee->email,
            'password' => 'password',
        ])->assertStatus(403);

        $this->postJson("/api/v1/enterprise/users/{$adminMembership->id}/remove", [
            'lead_action' => 'unassign',
        ])->assertStatus(422);

        $this->postJson("/api/v1/enterprise/users/{$destination->enterpriseMembership->id}/remove", [
            'lead_action' => 'transfer',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('transfer_to');
    }

    public function test_admin_can_update_branding_working_hours_and_logo(): void
    {
        Storage::fake('public');

        [$enterprise, $admin] = $this->makeEnterprise();
        [$manager] = $this->makeMember($enterprise, 'manager');

        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/enterprise/settings', [
            'name' => $enterprise->name,
            'email' => $enterprise->email,
            'rera_number' => 'RERA/2026/ACME-001',
            'working_hours' => [
                ['day' => 'monday', 'open' => '09:30', 'close' => '18:30', 'closed' => false],
                ['day' => 'sunday', 'closed' => true],
            ],
            'auto_assign_round_robin' => true,
            'billing_interval' => 'annual',
        ])->assertOk()
            ->assertJsonPath('data.rera_number', 'RERA/2026/ACME-001');

        $enterprise->refresh();
        $this->assertSame('RERA/2026/ACME-001', $enterprise->rera_number);
        $this->assertCount(2, $enterprise->working_hours);
        $this->assertTrue((bool) $enterprise->setting('auto_assign_round_robin'));

        $this->postJson('/api/v1/enterprise/settings/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ])->assertOk();

        $enterprise->refresh();
        $this->assertNotNull($enterprise->logo_path);
        Storage::disk('public')->assertExists($enterprise->logo_path);

        $this->deleteJson('/api/v1/enterprise/settings/logo')
            ->assertOk()
            ->assertJsonPath('data.logo_path', null);

        $this->assertNull($enterprise->fresh()->logo_path);

        Sanctum::actingAs($manager);
        $this->putJson('/api/v1/enterprise/settings', [
            'name' => 'Hijacked',
            'email' => 'hijack@example.com',
        ])->assertStatus(403);
    }

    public function test_plan_features_gate_export_and_meta_integrations(): void
    {
        $plan = $this->makePlan([
            'advanced_reports' => false,
            'meta_integrations' => false,
        ]);
        [$enterprise, $admin] = $this->makeEnterprise($plan);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/enterprise/reports/export?type=leads')
            ->assertStatus(403)
            ->assertJsonPath('code', 'feature_locked')
            ->assertJsonPath('feature', 'advanced_reports');

        $this->getJson('/api/v1/enterprise/meta/pages')
            ->assertStatus(403)
            ->assertJsonPath('code', 'feature_locked')
            ->assertJsonPath('feature', 'meta_integrations');

        $plan->update(['advanced_reports' => true, 'meta_integrations' => true]);

        $this->getJson('/api/v1/enterprise/reports/export?type=leads')->assertOk();
        $this->getJson('/api/v1/enterprise/meta/pages')
            ->assertOk()
            ->assertJsonPath('data.pages', []);
    }

    public function test_seat_addon_requests_can_be_approved_or_rejected_by_platform_admin(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $platformAdmin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/enterprise/billing/seat-requests', [
            'seat_type' => 'employees',
            'quantity' => 3,
            'note' => 'Expanding the sales floor',
        ])->assertCreated()
            ->assertJsonPath('data.request.status', 'pending');

        $this->postJson('/api/v1/enterprise/billing/seat-requests', [
            'seat_type' => 'employees',
            'quantity' => 1,
        ])->assertStatus(409);

        $seatRequestId = $enterprise->seatRequests()->firstOrFail()->id;

        Sanctum::actingAs($platformAdmin);

        $this->getJson('/api/v1/admin/seat-requests?status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $seatRequestId);

        $this->postJson("/api/v1/admin/seat-requests/{$seatRequestId}/approve", [
            'notes' => 'Approved for next billing cycle',
        ])->assertOk();

        $enterprise->refresh();
        $this->assertSame(3, $enterprise->extra_employees);
        $this->assertSame(23, app(EnterpriseLimitService::class)->limit($enterprise, 'employees'));
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'enterprise.seat_request_approved']);

        $this->postJson("/api/v1/admin/seat-requests/{$seatRequestId}/approve")->assertStatus(409);

        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/enterprise/billing/seat-requests', [
            'seat_type' => 'managers',
            'quantity' => 2,
        ])->assertCreated();

        $managerRequestId = $enterprise->seatRequests()
            ->where('seat_type', 'managers')
            ->firstOrFail()
            ->id;

        Sanctum::actingAs($platformAdmin);
        $this->postJson("/api/v1/admin/seat-requests/{$managerRequestId}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->postJson("/api/v1/admin/seat-requests/{$managerRequestId}/reject", [
            'reason' => 'Capacity review required first',
        ])->assertOk();

        $this->assertSame(0, $enterprise->fresh()->extra_managers);
        $this->assertDatabaseHas('enterprise_seat_requests', [
            'id' => $managerRequestId,
            'status' => 'rejected',
        ]);
    }

    public function test_enterprise_invoice_download_is_tenant_scoped_and_admin_only(): void
    {
        Storage::fake('public');

        [$enterprise, $admin] = $this->makeEnterprise();
        [, $otherAdmin] = $this->makeEnterprise(name: 'Other Realty');
        [$employee] = $this->makeMember($enterprise, 'employee');

        $invoice = Invoice::create([
            'invoice_number' => 'INV-ACME-001',
            'user_id' => $admin->id,
            'subtotal' => 1000,
            'tax' => 180,
            'discount' => 0,
            'total' => 1180,
            'status' => 'paid',
        ]);

        Storage::disk('public')->put("invoices/{$invoice->invoice_number}.pdf", '%PDF-1.4 test');
        $invoice->update(['pdf_path' => "invoices/{$invoice->invoice_number}.pdf"]);

        $foreignInvoice = Invoice::create([
            'invoice_number' => 'INV-OTHER-001',
            'user_id' => $otherAdmin->id,
            'subtotal' => 500,
            'tax' => 90,
            'discount' => 0,
            'total' => 590,
            'status' => 'paid',
        ]);

        Sanctum::actingAs($admin);
        $this->get("/api/v1/enterprise/billing/invoices/{$invoice->id}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get("/api/v1/enterprise/billing/invoices/{$foreignInvoice->id}/download")
            ->assertNotFound();

        Sanctum::actingAs($employee);
        $this->get("/api/v1/enterprise/billing/invoices/{$invoice->id}/download")
            ->assertStatus(403);
    }
}
