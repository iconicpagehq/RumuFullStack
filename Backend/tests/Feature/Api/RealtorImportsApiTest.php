<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RealtorImportsApiTest extends TestCase
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

    public function test_csv_import_requires_file(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $this->postJson("/api/v1/realtor/campaigns/{$campaign->id}/import/csv", [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_csv_import_accepts_valid_csv(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $csv = implode("\n", [
            'Name,Email,Phone,Company',
            'John Doe,john@example.com,9876543210,Acme Inc',
            'Jane Smith,jane@example.com,9123456780,Example Co',
        ]);

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $this->withHeader('Accept', 'application/json')
            ->post("/api/v1/realtor/campaigns/{$campaign->id}/import/csv", [
                'csv_file' => $file,
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 2);
    }

    public function test_xlsx_import_accepts_workbook(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Name', 'Email', 'Phone', 'Company'],
            ['Excel Lead A', 'excel.a.realtor@test.com', '9876501111', 'Acme'],
            ['Excel Lead B', 'excel.b.realtor@test.com', '9876502222', 'Beta'],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'realtor-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile(
            $path,
            'leads.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->withHeader('Accept', 'application/json')
            ->post("/api/v1/realtor/campaigns/{$campaign->id}/import/csv", [
                'csv_file' => $file,
            ])->assertOk()
            ->assertJsonPath('data.imported', 2);

        $this->assertDatabaseHas('leads', [
            'campaign_id' => $campaign->id,
            'email' => 'excel.a.realtor@test.com',
            'source' => 'csv',
        ]);
    }

    public function test_sample_xlsx_download_is_available(): void
    {
        $realtor = $this->createRealtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $this->get("/api/v1/realtor/campaigns/{$campaign->id}/import/sample?format=xlsx")
            ->assertOk()
            ->assertDownload("leads-sample-campaign-{$campaign->id}.xlsx");
    }
}

