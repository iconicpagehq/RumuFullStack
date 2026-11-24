<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\InventoryProject;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Services\InventoryAvailabilityService;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadCsvMapper;
use App\Services\MetaLeadImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class LeadController extends Controller
{
    public function __construct(
        private InventoryAvailabilityService $inventoryAvailability
    ) {}

    /**
     * Maximum number of errors to store before stopping (prevents memory leak)
     */
    private const MAX_ERRORS = 100;

    /**
     * Expected CSV headers
     */
    private const EXPECTED_HEADERS = ['Name', 'Email', 'Phone', 'Company'];

    /**
     * Download a sample CSV for imports.
     */
    public function downloadSampleCsv(Campaign $campaign)
    {
        $this->authorize('create', [Lead::class, $campaign]);

        $filename = 'leads-sample-campaign-' . $campaign->id . '.csv';
        $rows = [
            self::EXPECTED_HEADERS,
            ['John Doe', 'john@example.com', '9876543210', 'Acme Inc'],
            ['Jane Smith', 'jane@example.com', '9123456780', 'Example Co'],
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

    /**
     * Display a listing of leads for a campaign
     */
    public function index(Campaign $campaign)
    {
        $this->authorize('viewAny', [Lead::class, $campaign]);

        $leads = $campaign->leads()->latest()->get();
        return view('campaigns.show', compact('campaign', 'leads'));
    }

    /**
     * Show the form for creating a new lead
     */
    public function create(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $inventoryProjects = $this->getInventoryProjectsForUser();

        return view('leads.create', compact('campaign', 'inventoryProjects'));
    }

    /**
     * Store a newly created lead
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('create', [Lead::class, $campaign]);

        // Check plan limits
        if (!Auth::user()->canAddLeadToCampaign($campaign)) {
            return redirect()->back()
                ->with('error', 'You have reached the lead limit for this campaign. Please upgrade your plan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!empty($value) && !$this->isValidPhone($value)) {
                    $fail('The phone number format is invalid.');
                }
            }],
            'company' => 'nullable|string|max:255',
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'note' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_fields.client_type' => 'nullable|in:buyer,seller,investor,tenant',
            'custom_fields.budget_range' => 'nullable|in:below_50l,50l_75l,75l_1cr,1cr_1.5cr,1.5cr_2cr,2cr_3cr,3cr_5cr,above_5cr',
            'custom_fields.purpose' => 'nullable|in:self_use,investment,rental',
            'custom_fields.family_size' => 'nullable|in:1,2,3,4,5,5+',
            'custom_fields.property_type' => 'nullable|in:apartment,villa,plot,commercial,office,shop',
            'custom_fields.configuration' => 'nullable|in:studio,1_bhk,2_bhk,3_bhk,4_bhk,4+_bhk',
            'custom_fields.preferred_location' => 'nullable|string|max:255',
            'custom_fields.project_name' => 'nullable|string|max:255',
            'custom_fields.construction_status' => 'nullable|in:ready_to_move,under_construction,both',
            'custom_fields.city' => 'nullable|string|max:255',
            'custom_fields.site_visit_required' => 'nullable|in:yes,no',
            'custom_fields.preferred_visit_date' => 'nullable|date',
            'custom_fields.landmarks' => 'nullable|string|max:500',
            'custom_fields.lead_source' => 'nullable|in:facebook,google,portal,walk_in,referral,other',
            'custom_fields.lead_priority' => 'nullable|in:hot,warm,cold',
            'custom_fields.buying_timeline' => 'nullable|in:immediate,1_3_months,3_6_months,6_12_months,just_browsing',
            'custom_fields.funding_type' => 'nullable|in:self,loan,bank_approved',
            'inventory_unit_id' => 'nullable|exists:inventory_units,id',
        ]);

        $lead = $campaign->leads()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company' => $validated['company'],
            'status' => $validated['status'],
            'source' => 'manual',
            'custom_fields' => $this->mergeProjectNameFromUnit($validated['custom_fields'] ?? [], $validated['inventory_unit_id'] ?? null),
            'inventory_unit_id' => $validated['inventory_unit_id'] ?? null,
        ]);

        try {
            $this->syncLeadInventory($lead, $validated['inventory_unit_id'] ?? null);
        } catch (\InvalidArgumentException $e) {
            $lead->delete();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if (!empty($validated['note'])) {
            $lead->addNote($validated['note'], Auth::id());
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Lead added successfully!');
    }

    /**
     * Display the specified lead
     */
    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);

        $lead->load([
            'notes.user',
            'campaign',
            'inventoryUnit.floor.wing',
            'inventoryUnit.floor.tower.project',
            'metaCapiEvents' => fn ($q) => $q->limit(5),
        ]);

        $metaCapiConfigured = $lead->campaign->metaCapiSettings?->is_active ?? false;

        return view('leads.show', compact('lead', 'metaCapiConfigured'));
    }

    /**
     * Show the form for editing the specified lead
     */
    public function edit(Lead $lead)
    {
        $this->authorize('update', $lead);

        $inventoryProjects = $this->getInventoryProjectsForUser();

        return view('leads.edit', compact('lead', 'inventoryProjects'));
    }

    /**
     * Update the specified lead
     */
    public function update(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!empty($value) && !$this->isValidPhone($value)) {
                    $fail('The phone number format is invalid.');
                }
            }],
            'company' => 'nullable|string|max:255',
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'custom_fields' => 'nullable|array',
            'custom_fields.client_type' => 'nullable|in:buyer,seller,investor,tenant',
            'custom_fields.budget_range' => 'nullable|in:below_50l,50l_75l,75l_1cr,1cr_1.5cr,1.5cr_2cr,2cr_3cr,3cr_5cr,above_5cr',
            'custom_fields.purpose' => 'nullable|in:self_use,investment,rental',
            'custom_fields.family_size' => 'nullable|in:1,2,3,4,5,5+',
            'custom_fields.property_type' => 'nullable|in:apartment,villa,plot,commercial,office,shop',
            'custom_fields.configuration' => 'nullable|in:studio,1_bhk,2_bhk,3_bhk,4_bhk,4+_bhk',
            'custom_fields.preferred_location' => 'nullable|string|max:255',
            'custom_fields.project_name' => 'nullable|string|max:255',
            'custom_fields.construction_status' => 'nullable|in:ready_to_move,under_construction,both',
            'custom_fields.city' => 'nullable|string|max:255',
            'custom_fields.site_visit_required' => 'nullable|in:yes,no',
            'custom_fields.preferred_visit_date' => 'nullable|date',
            'custom_fields.landmarks' => 'nullable|string|max:500',
            'custom_fields.lead_source' => 'nullable|in:facebook,google,portal,walk_in,referral,other',
            'custom_fields.lead_priority' => 'nullable|in:hot,warm,cold',
            'custom_fields.buying_timeline' => 'nullable|in:immediate,1_3_months,3_6_months,6_12_months,just_browsing',
            'custom_fields.funding_type' => 'nullable|in:self,loan,bank_approved',
            'inventory_unit_id' => 'nullable|exists:inventory_units,id',
            'note' => 'nullable|string',
        ]);

        $leadData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company' => $validated['company'],
            'status' => $validated['status'],
            'custom_fields' => $this->mergeProjectNameFromUnit($validated['custom_fields'] ?? [], $validated['inventory_unit_id'] ?? null),
            'inventory_unit_id' => $validated['inventory_unit_id'] ?? null,
        ];

        $previousUnitId = $lead->inventory_unit_id;

        $lead->update($leadData);

        try {
            $this->syncLeadInventory($lead->fresh(), $validated['inventory_unit_id'] ?? null, $previousUnitId);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if (!empty($validated['note'])) {
            $lead->addNote($validated['note'], Auth::id());
        }

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully!');
    }

    /**
     * Process CSV Import
     */
    public function importCsv(Request $request, Campaign $campaign)
    {
        $this->authorize('create', [Lead::class, $campaign]);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        
        // Check file encoding and convert if necessary
        $fileContent = file_get_contents($file->getPathname());
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            file_put_contents($file->getPathname(), $fileContent);
        }

        $handle = fopen($file->getPathname(), 'r');
        
        // Read and validate header
        $header = fgetcsv($handle);
        
        if (!$this->validateHeader($header)) {
            fclose($handle);
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Invalid CSV header. Expected columns: ' . implode(', ', self::EXPECTED_HEADERS) . '. Found: ' . ($header ? implode(', ', $header) : 'none'));
        }

        // Count total rows first to check limits before processing
        $rowCount = 0;
        $tempHandle = fopen($file->getPathname(), 'r');
        fgetcsv($tempHandle); // Skip header
        while (fgetcsv($tempHandle) !== false) {
            $rowCount++;
        }
        fclose($tempHandle);

        // Check if user has enough capacity for all leads
        $user = Auth::user();
        $currentLeadCount = $campaign->leads()->count();
        $maxLeads = $user->plan?->max_leads_per_campaign;
        
        if ($maxLeads !== null && ($currentLeadCount + $rowCount) > $maxLeads) {
            $canAdd = $maxLeads - $currentLeadCount;
            fclose($handle);
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', "CSV has {$rowCount} rows but you can only add {$canAdd} more leads to this campaign. Please upgrade your plan or reduce the CSV size.");
        }

        $count = 0;
        $errors = [];
        $rowNumber = 1;
        $maxErrorsReached = false;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Stop processing if we've collected too many errors (prevent memory leak)
            if (count($errors) >= self::MAX_ERRORS) {
                $maxErrorsReached = true;
                break;
            }

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map data using header positions
            $data = $this->mapRowToData($row, $header);

            if (empty($data['name'])) {
                $errors[] = "Row $rowNumber: Name is required";
                continue;
            }

            // Validate phone format
            if (!empty($data['phone']) && !$this->isValidPhone($data['phone'])) {
                $errors[] = "Row $rowNumber: Invalid phone number format";
                continue;
            }

            // Check limit for each row
            if (!$user->canAddLeadToCampaign($campaign)) {
                $errors[] = "Plan limit reached at row $rowNumber. Upgrade plan to add more leads.";
                break;
            }

            try {
                $campaign->leads()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'company' => $data['company'],
                    'status' => 'new',
                    'source' => 'csv',
                ]);
                $count++;
            } catch (\Exception $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
                Log::error('CSV Import Error', ['row' => $rowNumber, 'error' => $e->getMessage()]);
            }
        }

        fclose($handle);

        $message = "Imported $count leads successfully.";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " rows failed.";
            if ($maxErrorsReached) {
                $message .= " (Stopped after " . self::MAX_ERRORS . " errors to prevent memory issues)";
            }
            return redirect()->route('campaigns.show', $campaign)
                ->with('warning', $message)
                ->with('import_errors', $errors);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', $message);
    }

    /**
     * Show Meta Ads import page (CSV + API sync).
     */
    public function showMetaImport(Campaign $campaign)
    {
        $this->authorize('create', [Lead::class, $campaign]);

        $user = Auth::user();
        $metaPages = $user->metaPages()->where('status', 'connected')->get();

        $mapping = \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
            ->where('campaign_id', $campaign->id)
            ->first();

        return view('leads.import-meta', [
            'campaign' => $campaign,
            'facebookLinked' => (bool) $user->facebook_id,
            'metaPages' => $metaPages,
            'savedFormId' => $campaign->meta_lead_form_id,
            'savedPageId' => $mapping ? $mapping->page_id : null,
        ]);
    }

    /**
     * Download sample Meta Ads export CSV.
     */
    public function downloadMetaSampleCsv(Campaign $campaign)
    {
        $this->authorize('create', [Lead::class, $campaign]);

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

    /**
     * Import leads from Meta Ads CSV export.
     */
    public function importMetaCsv(Request $request, Campaign $campaign, MetaLeadCsvMapper $mapper)
    {
        $this->authorize('create', [Lead::class, $campaign]);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $fileContent = file_get_contents($file->getPathname());
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            file_put_contents($file->getPathname(), $fileContent);
        }

        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);
        if (empty($header)) {
            fclose($handle);
            return redirect()->route('leads.import.meta.page', $campaign)
                ->with('error', 'CSV file is empty or invalid.');
        }

        $user = Auth::user();
        $count = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;
        $maxErrorsReached = false;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count($errors) >= self::MAX_ERRORS) {
                $maxErrorsReached = true;
                break;
            }
            if (empty(array_filter($row))) {
                continue;
            }

            $data = $mapper->mapRow($row, $header);
            if (!$mapper->hasValidLeadData($data)) {
                $errors[] = "Row $rowNumber: Name or email/phone is required";
                continue;
            }

            $data['name'] = $mapper->resolveDisplayName($data);
            $result = $this->importMetaLeadRecord($campaign, $data, $user);
            if ($result === 'imported') {
                $count++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } elseif ($result === 'limit') {
                $errors[] = "Plan limit reached at row $rowNumber. Upgrade plan to add more leads.";
                break;
            }
        }

        fclose($handle);

        $message = "Imported $count Meta leads successfully.";
        if ($skipped > 0) {
            $message .= " Skipped $skipped duplicate(s).";
        }
        if (count($errors) > 0) {
            $message .= ' ' . count($errors) . ' rows failed.';
            if ($maxErrorsReached) {
                $message .= ' (Stopped after ' . self::MAX_ERRORS . ' errors)';
            }
            return redirect()->route('campaigns.show', $campaign)
                ->with('warning', $message)
                ->with('import_errors', $errors);
        }

        return redirect()->route('campaigns.show', $campaign)->with('success', $message);
    }

    /**
     * List Meta leadgen forms (JSON).
     */
    public function listMetaForms(Request $request, Campaign $campaign, MetaLeadApiService $metaApi)
    {
        $this->authorize('create', [Lead::class, $campaign]);
        
        $request->validate(['page_id' => 'required|string']);
        
        $metaPage = Auth::user()->metaPages()->where('page_id', $request->page_id)->first();
        if (!$metaPage) {
            return response()->json(['error' => 'Facebook Page not found or disconnected.'], 404);
        }

        if ($request->boolean('refresh')) {
            \Illuminate\Support\Facades\Cache::forget("meta_lead_forms_{$metaPage->page_id}");
        }

        try {
            $forms = $metaApi->listLeadForms($metaPage);
            return response()->json(['forms' => $forms]);
        } catch (\App\Exceptions\MetaApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Sync leads from Meta Lead Form via Graph API.
     */
    public function syncMetaLeads(
        Request $request,
        Campaign $campaign,
        MetaLeadApiService $metaApi,
        MetaLeadImportService $metaImport
    ) {
        $this->authorize('create', [Lead::class, $campaign]);

        $request->validate([
            'form_id' => 'required|string',
            'page_id' => 'required|string',
        ]);
        
        $metaPage = Auth::user()->metaPages()->where('page_id', $request->page_id)->first();
        if (!$metaPage) {
            return redirect()->route('leads.import.meta.page', $campaign)
                ->with('error', 'Associated Facebook Page not found or disconnected.');
        }

        $mapping = \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
            ->where('campaign_id', $campaign->id)
            ->where('form_id', $request->form_id)
            ->first();
            
        if (!$mapping) {
            $formName = 'Unknown Form';
            
            if ($request->form_id === 'all') {
                $formName = 'All Forms';
            } else {
                try {
                    $forms = $metaApi->listLeadForms($metaPage);
                    foreach ($forms as $f) {
                        if ((string)$f['id'] === (string)$request->form_id) {
                            $formName = $f['name'];
                            break;
                        }
                    }
                } catch (\Exception $e) {}
            }

            \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->updateOrInsert(
                ['campaign_id' => $campaign->id],
                [
                    'form_id' => $request->form_id,
                    'page_id' => $request->page_id,
                    'form_name' => $formName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $result = $metaImport->syncFromApi($campaign, Auth::user(), $request->form_id, $metaPage, $metaApi);

        if (!empty($result['errors']) && $result['imported'] === 0) {
            return redirect()->route('leads.import.meta.page', $campaign)
                ->with('error', $result['message']);
        }

        if (!empty($result['errors'])) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('warning', $result['message']);
        }

        return redirect()->route('campaigns.show', $campaign)->with('success', $result['message']);
    }

    /**
     * Resend Meta CAPI event for the lead's current status (debug/support).
     */
    public function resendMetaCapi(Lead $lead)
    {
        $this->authorize('update', $lead);

        if (empty($lead->meta_lead_id)) {
            return redirect()->back()->with('error', 'This lead has no Meta lead ID.');
        }

        // Just fire the event to trigger the listener
        event(new \App\Events\LeadStatusUpdated($lead));

        return redirect()->back()->with('success', 'Meta event resent successfully.');
    }

    /**
     * Add note to lead
     */
    public function addNote(Request $request, Lead $lead)
    {
        $this->authorize('addNote', $lead);

        $request->validate([
            'note' => 'required|string',
        ]);

        $lead->addNote($request->note, Auth::id());

        return redirect()->back()->with('success', 'Note added successfully!');
    }

    /**
     * Update lead status
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorize('updateStatus', $lead);

        $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost'
        ]);

        $lead->update(['status' => $request->status]);

        $this->inventoryAvailability->syncFromLeadStatus($lead->fresh(), Auth::user());

        // Add automatic system note
        $lead->addNote("Status updated to " . ucfirst($request->status), Auth::id());

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    /**
     * Validate CSV header
     */
    private function validateHeader(?array $header): bool
    {
        if (empty($header)) {
            return false;
        }

        // Normalize header (lowercase, trim)
        $normalizedHeader = array_map(function ($col) {
            return strtolower(trim($col));
        }, $header);

        $normalizedExpected = array_map('strtolower', self::EXPECTED_HEADERS);

        // Check if all expected columns are present
        foreach ($normalizedExpected as $expected) {
            if (!in_array($expected, $normalizedHeader)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Map CSV row to data array using header positions
     */
    private function mapRowToData(array $row, array $header): array
    {
        // Normalize header
        $normalizedHeader = array_map(function ($col) {
            return strtolower(trim($col));
        }, $header);

        $data = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'company' => null,
        ];

        // Find column positions and map data
        $nameIndex = array_search('name', $normalizedHeader);
        $emailIndex = array_search('email', $normalizedHeader);
        $phoneIndex = array_search('phone', $normalizedHeader);
        $companyIndex = array_search('company', $normalizedHeader);

        if ($nameIndex !== false) {
            $data['name'] = trim($row[$nameIndex] ?? '');
        }
        if ($emailIndex !== false) {
            $data['email'] = trim($row[$emailIndex] ?? '');
        }
        if ($phoneIndex !== false) {
            $data['phone'] = trim($row[$phoneIndex] ?? '');
        }
        if ($companyIndex !== false) {
            $data['company'] = trim($row[$companyIndex] ?? '');
        }

        return $data;
    }

    /**
     * Validate phone number format
     */
    private function isValidPhone(string $phone): bool
    {
        // Allow common phone formats:
        // +1234567890, 123-456-7890, (123) 456-7890, 123.456.7890, etc.
        $pattern = '/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/';
        return preg_match($pattern, $phone) === 1;
    }

    private function getInventoryProjectsForUser()
    {
        if (Auth::user()->role !== 'realtor') {
            return collect();
        }

        return InventoryProject::byUser(Auth::id())->active()->orderBy('name')->get();
    }

    private function syncLeadInventory(Lead $lead, ?int $unitId, ?int $previousUnitId = null): void
    {
        if ($previousUnitId && $previousUnitId !== $unitId) {
            $previous = InventoryUnit::find($previousUnitId);
            if ($previous) {
                $this->inventoryAvailability->releaseUnit($previous, Auth::user(), 'Unlinked from lead');
            }
        }

        if (!$unitId) {
            return;
        }

        $unit = InventoryUnit::with('floor.tower.project')->findOrFail($unitId);

        if ($unit->floor->tower->project->user_id !== $lead->campaign->user_id) {
            throw new \InvalidArgumentException('Selected unit does not belong to your inventory.');
        }

        $this->inventoryAvailability->assignToLead($unit, $lead, Auth::user());
        $this->inventoryAvailability->syncFromLeadStatus($lead->fresh(), Auth::user());
    }

    private function mergeProjectNameFromUnit(?array $customFields, ?int $unitId): ?array
    {
        $customFields = $customFields ?? [];

        if (!$unitId) {
            return $customFields ?: null;
        }

        $unit = InventoryUnit::with('floor.tower.project')->find($unitId);
        if ($unit?->floor?->tower?->project) {
            $customFields['project_name'] = $unit->floor->tower->project->name;
        }

        return $customFields ?: null;
    }
}
