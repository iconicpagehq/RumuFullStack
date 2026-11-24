<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Models\Campaign;
use App\Services\LeadCsvImportService;
use App\Services\LeadImportFileService;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadCsvMapper;
use App\Services\MetaLeadImportService;
use Illuminate\Http\Request;

class LeadImportController extends ApiController
{
    public function __construct(
        private LeadCsvImportService $csvImport,
        private MetaLeadImportService $metaImport,
        private LeadImportFileService $files,
    ) {}

    public function importCsv(Request $request, Campaign $campaign)
    {
        $this->authorize('create', [\App\Models\Lead::class, $campaign]);

        $validated = $request->validate([
            'csv_file' => 'required|file|' . LeadImportFileService::VALIDATION_RULE . '|max:2048',
        ]);

        $result = $this->csvImport->import($campaign, $request->user(), $validated['csv_file']);

        if (!empty($result['errors']) && $result['imported'] === 0) {
            return $this->error($result['message'], 422, ['csv_file' => $result['errors']]);
        }

        return $this->success($result, $result['message']);
    }

    public function downloadSampleCsv(Request $request, Campaign $campaign)
    {
        $this->authorize('create', [\App\Models\Lead::class, $campaign]);

        $rows = [
            ['Name', 'Email', 'Phone', 'Company'],
            ['John Doe', 'john@example.com', '9876543210', 'Acme Inc'],
            ['Jane Smith', 'jane@example.com', '9123456780', 'Example Co'],
        ];

        return $this->files->sampleDownload(
            $rows,
            'leads-sample-campaign-' . $campaign->id,
            $request->query('format')
        );
    }

    public function downloadMetaSampleCsv(Campaign $campaign)
    {
        $this->authorize('create', [\App\Models\Lead::class, $campaign]);

        $filename = 'meta-ads-sample-campaign-' . $campaign->id . '.csv';
        $rows = [
            ['id', 'created_time', 'full_name', 'email', 'phone_number', 'campaign_name', 'ad_name'],
            ['123456789', '2026-06-02 10:00:00', 'John Doe', 'john@example.com', '9876543210', 'Summer Campaign', 'Lead Ad 1'],
            ['123456790', '2026-06-02 11:00:00', 'Jane Smith', 'jane@example.com', '9123456780', 'Summer Campaign', 'Lead Ad 2'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function listMetaForms(Request $request, Campaign $campaign, MetaLeadApiService $metaApi)
    {
        $this->authorize('create', [\App\Models\Lead::class, $campaign]);

        $request->validate(['page_id' => 'required|string']);

        $metaPage = $request->user()->metaPages()->where('page_id', $request->page_id)->first();
        if (!$metaPage) {
            return $this->error('Facebook Page not found or disconnected.', 422);
        }

        try {
            if ($request->boolean('refresh')) {
                \Illuminate\Support\Facades\Cache::forget("meta_lead_forms_{$metaPage->page_id}");
            }
            $forms = $metaApi->listLeadForms($metaPage);
            return $this->success(['forms' => $forms]);
        } catch (\App\Exceptions\MetaApiException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error('An unexpected error occurred.', 500);
        }
    }

    public function importMetaCsv(Request $request, Campaign $campaign, MetaLeadCsvMapper $mapper)
    {
        $this->authorize('create', [\App\Models\Lead::class, $campaign]);

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $result = $this->metaImport->importFromCsv($campaign, $request->user(), $validated['csv_file'], $mapper);

        if (!empty($result['errors']) && $result['imported'] === 0) {
            return $this->error($result['message'], 422, ['csv_file' => $result['errors']]);
        }

        return $this->success($result, $result['message']);
    }

    public function syncMetaLeads(Request $request, Campaign $campaign, MetaLeadApiService $metaApi)
    {
        $this->authorize('create', [\App\Models\Lead::class, $campaign]);

        $validated = $request->validate([
            'form_id' => 'required|string',
            'page_id' => 'nullable|string',
        ]);

        $pageId = $validated['page_id'] ?? null;

        if (!$pageId) {
            $pageId = \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
                ->where('campaign_id', $campaign->id)
                ->where('form_id', $validated['form_id'])
                ->value('page_id');
        }

        if (!$pageId) {
            $metaPages = $request->user()->metaPages();
            if ($metaPages->count() === 1) {
                $pageId = $metaPages->value('page_id');
            }
        }

        if (!$pageId) {
            return $this->error(
                'Associated Facebook Page not found or disconnected.',
                422,
                ['page_id' => ['The page id field is required.']]
            );
        }

        $metaPage = $request->user()->metaPages()->where('page_id', $pageId)->first();
        if (!$metaPage) {
            return $this->error('Associated Facebook Page not found or disconnected.', 422);
        }

        $mapping = \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
            ->where('campaign_id', $campaign->id)
            ->where('form_id', $validated['form_id'])
            ->first();

        if (!$mapping) {
            $formName = 'Unknown Form';

            if ($validated['form_id'] === 'all') {
                $formName = 'All Forms';
            } else {
                try {
                    foreach ($metaApi->listLeadForms($metaPage) as $form) {
                        if ((string) ($form['id'] ?? '') === $validated['form_id']) {
                            $formName = $form['name'] ?? $formName;
                            break;
                        }
                    }
                } catch (\Exception) {
                    // Fall back to the generic name; syncing can still proceed.
                }
            }

            \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->updateOrInsert(
                ['campaign_id' => $campaign->id],
                [
                    'form_id' => $validated['form_id'],
                    'page_id' => $pageId,
                    'form_name' => $formName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $result = $this->metaImport->syncFromApi($campaign, $request->user(), $validated['form_id'], $metaPage, $metaApi);

        if (!empty($result['errors']) && $result['imported'] === 0) {
            return $this->error($result['message'], 422, ['form_id' => $result['errors']]);
        }

        return $this->success($result, $result['message']);
    }
}

