<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EnterpriseLeadImportTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'key' => 'enterprise-import-' . Str::lower(Str::random(8)),
            'name' => 'Enterprise Import ' . Str::upper(Str::random(4)),
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

    private function makeEnterprise(?Plan $plan = null): array
    {
        $plan ??= $this->makePlan();
        $admin = User::factory()->create([
            'role' => 'enterprise',
            'plan_id' => $plan->id,
        ]);

        $enterprise = Enterprise::create([
            'name' => 'Import Realty',
            'email' => Str::lower(Str::random(8)) . '@enterprise.test',
            'owner_id' => $admin->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
            'settings' => [],
        ]);

        EnterpriseMembership::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return [$enterprise, $admin];
    }

    private function makeManager(Enterprise $enterprise, Team $team): User
    {
        $user = User::factory()->create([
            'role' => 'enterprise',
            'plan_id' => $enterprise->plan_id,
        ]);

        EnterpriseMembership::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        TeamMembership::create([
            'enterprise_id' => $enterprise->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $team->update(['manager_id' => $user->id]);

        return $user;
    }

    private function makeEmployee(Enterprise $enterprise): User
    {
        $user = User::factory()->create([
            'role' => 'enterprise',
            'plan_id' => $enterprise->plan_id,
        ]);

        EnterpriseMembership::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $user->id,
            'role' => 'employee',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user;
    }

    private function makeTeam(Enterprise $enterprise, string $name = 'Sales Team'): Team
    {
        return Team::create([
            'enterprise_id' => $enterprise->id,
            'name' => $name,
            'status' => 'active',
            'lead_distribution_mode' => 'manual',
        ]);
    }

    private function makeLead(Enterprise $enterprise, array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'enterprise_id' => $enterprise->id,
            'name' => 'Existing Lead',
            'email' => Str::lower(Str::random(8)) . '@existing.test',
            'phone' => '90000' . random_int(10000, 99999),
            'status' => 'new',
            'enterprise_stage' => 'new',
            'priority' => 'medium',
            'source' => 'manual',
        ], $overrides));
    }

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('leads.csv', $content);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function spreadsheetFile(array $rows, string $extension = 'xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'lead-import-') . '.' . $extension;
        $writer = $extension === 'xls' ? new Xls($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'leads.' . $extension,
            $extension === 'xls'
                ? 'application/vnd.ms-excel'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function csv(array $rows): string
    {
        return implode("\n", array_merge(['Name,Email,Phone,Company'], $rows));
    }

    public function test_admin_imports_csv_with_team_and_campaign_and_skips_duplicates(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise);
        $campaign = Campaign::create([
            'user_id' => $admin->id,
            'enterprise_id' => $enterprise->id,
            'name' => 'Monsoon Launch',
            'status' => 'active',
        ]);

        $this->makeLead($enterprise, [
            'email' => 'existing@lead.test',
            'phone' => '9000000001',
        ]);

        Sanctum::actingAs($admin);

        $csv = $this->csv([
            'Ada Lovelace,ada@lead.test,9876500001,Acme',
            'Grace Hopper,grace@lead.test,9876500002,Beta',
            'Existing Person,existing@lead.test,9000000001,Gamma',
            'Ada Duplicate,ada@lead.test,9876500009,Acme',
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile($csv),
                'team_id' => $team->id,
                'campaign_id' => $campaign->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.skipped', 2)
            ->assertJsonPath('data.failed', 0);

        $this->assertDatabaseHas('leads', [
            'enterprise_id' => $enterprise->id,
            'email' => 'ada@lead.test',
            'team_id' => $team->id,
            'campaign_id' => $campaign->id,
            'source' => 'csv',
            'status' => 'new',
            'enterprise_stage' => 'new',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'enterprise_id' => $enterprise->id,
            'user_id' => $admin->id,
            'type' => 'created',
            'title' => 'Lead imported from CSV',
        ]);

        $this->assertDatabaseHas('enterprise_audit_logs', [
            'enterprise_id' => $enterprise->id,
            'action' => 'leads.imported',
        ]);

        $this->assertSame(3, Lead::where('enterprise_id', $enterprise->id)->count());
    }

    public function test_manager_import_defaults_to_own_team_and_cannot_target_another_team(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $ownTeam = $this->makeTeam($enterprise, 'Own Team');
        $otherTeam = $this->makeTeam($enterprise, 'Other Team');
        $manager = $this->makeManager($enterprise, $ownTeam);

        Sanctum::actingAs($manager);

        $csv = $this->csv(['Manager Lead,manager.lead@test.com,9876511111,Acme']);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile($csv),
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 1);

        $this->assertDatabaseHas('leads', [
            'enterprise_id' => $enterprise->id,
            'email' => 'manager.lead@test.com',
            'team_id' => $ownTeam->id,
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile($this->csv(['Blocked Lead,blocked@test.com,9876522222,Acme'])),
                'team_id' => $otherTeam->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['team_id']);

        $this->assertDatabaseMissing('leads', ['email' => 'blocked@test.com']);
    }

    public function test_employee_cannot_import_leads(): void
    {
        [$enterprise] = $this->makeEnterprise();
        $employee = $this->makeEmployee($enterprise);

        Sanctum::actingAs($employee);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile($this->csv(['Nope,nope@test.com,9876533333,Acme'])),
            ])
            ->assertStatus(403);
    }

    public function test_import_is_blocked_when_monthly_lead_limit_would_be_exceeded(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise($this->makePlan(['max_monthly_leads' => 1]));

        Sanctum::actingAs($admin);

        $csv = $this->csv([
            'First Lead,first@test.com,9876544444,Acme',
            'Second Lead,second@test.com,9876555555,Beta',
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile($csv),
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.imported', 0);

        $this->assertSame(0, Lead::where('enterprise_id', $enterprise->id)->count());
    }

    public function test_invalid_csv_header_returns_validation_errors(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();

        Sanctum::actingAs($admin);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile("Full Name,Mobile\nAda Lovelace,98765"),
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.imported', 0);
    }

    public function test_rows_without_contact_details_are_reported_and_others_import(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();

        Sanctum::actingAs($admin);

        $csv = $this->csv([
            'Valid Lead,valid@test.com,9876566666,Acme',
            'No Contact,,,Acme',
            'Bad Email,not-an-email,9876577777,Acme',
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $this->csvFile($csv),
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 1)
            ->assertJsonPath('data.failed', 2);

        $this->assertDatabaseHas('leads', ['email' => 'valid@test.com']);
        $this->assertDatabaseMissing('leads', ['name' => 'No Contact']);
    }

    public function test_sample_csv_download_is_available_for_admins(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();

        Sanctum::actingAs($admin);

        $this->get('/api/v1/enterprise/leads/import/sample')
            ->assertOk()
            ->assertDownload('leads-sample-enterprise.csv');
    }

    public function test_admin_imports_xlsx_workbook(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();
        $team = $this->makeTeam($enterprise);

        Sanctum::actingAs($admin);

        $file = $this->spreadsheetFile([
            ['Name', 'Email', 'Phone', 'Company'],
            ['Xlsx Lead A', 'xlsx.a.import@test.com', '9876588881', 'Acme'],
            ['Xlsx Lead B', 'xlsx.b.import@test.com', '9876588882', 'Beta'],
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $file,
                'team_id' => $team->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.failed', 0);

        $this->assertDatabaseHas('leads', [
            'enterprise_id' => $enterprise->id,
            'email' => 'xlsx.a.import@test.com',
            'team_id' => $team->id,
            'source' => 'csv',
        ]);
    }

    public function test_admin_imports_legacy_xls_workbook(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();

        Sanctum::actingAs($admin);

        $file = $this->spreadsheetFile([
            ['Name', 'Email', 'Phone', 'Company'],
            ['Legacy Lead', 'legacy.xls.import@test.com', '9876588883', 'Acme'],
        ], 'xls');

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/enterprise/leads/import', [
                'csv_file' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 1);

        $this->assertDatabaseHas('leads', ['email' => 'legacy.xls.import@test.com']);
    }

    public function test_sample_xlsx_download_is_available(): void
    {
        [$enterprise, $admin] = $this->makeEnterprise();

        Sanctum::actingAs($admin);

        $this->get('/api/v1/enterprise/leads/import/sample?format=xlsx')
            ->assertOk()
            ->assertDownload('leads-sample-enterprise.xlsx');
    }
}
