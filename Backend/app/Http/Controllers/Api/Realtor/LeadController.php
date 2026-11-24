<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\LeadResource;
use App\Models\Campaign;
use App\Models\Lead;
use App\Services\InventoryAvailabilityService;
use App\Services\LeadInventorySyncService;
use Illuminate\Http\Request;

class LeadController extends ApiController
{
    public function __construct(
        private LeadInventorySyncService $leadInventory,
        private InventoryAvailabilityService $availabilityService
    ) {}

    public function index(Request $request, Campaign $campaign)
    {
        $this->authorize('viewAny', [Lead::class, $campaign]);

        $leads = $campaign->leads()
            ->with(['inventoryUnit.floor.tower.project'])
            ->recent()
            ->paginate(20);

        return $this->paginated(LeadResource::collection($leads));
    }

    public function all(Request $request)
    {
        $query = $this->globalLeadQuery($request);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($campaignId = $request->input('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $statusCounts = $this->globalLeadQuery($request)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $paginator = $query->with(['campaign', 'inventoryUnit.floor.tower.project'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => LeadResource::collection($paginator->items())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'status_counts' => (object) $statusCounts->all(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->globalLeadQuery($request)
            ->with('campaign:id,name')
            ->when($request->filled('status'), fn ($inner) => $inner->where('status', $request->string('status')))
            ->when($request->filled('campaign_id'), fn ($inner) => $inner->where('campaign_id', $request->integer('campaign_id')))
            ->latest();

        $filename = 'my_leads_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Lead ID', 'Name', 'Phone', 'Email', 'Company', 'Campaign', 'Status', 'Priority', 'Source', 'Budget Min', 'Budget Max', 'Deal Value', 'Created At']);
            $query->cursor()->each(function (Lead $lead) use ($out) {
                fputcsv($out, array_map(fn ($value) => $this->csvSafe($value), [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->company,
                    $lead->campaign?->name,
                    $lead->status,
                    $lead->priority,
                    $lead->source,
                    $lead->budget_min,
                    $lead->budget_max,
                    $lead->deal_value,
                    $lead->created_at?->toDateTimeString(),
                ]));
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function globalLeadQuery(Request $request)
    {
        return Lead::query()
            ->whereNull('enterprise_id')
            ->forRealtor($request->user()->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            });
    }

    private function csvSafe(mixed $value): string
    {
        $string = $value === null ? '' : (string) $value;

        if ($string !== '' && preg_match('/^[=+\-@\t\r]/', $string)) {
            return "'" . $string;
        }

        return $string;
    }

    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('create', [Lead::class, $campaign]);

        if (!$request->user()->canAddLeadToCampaign($campaign)) {
            return $this->error('You have reached the lead limit for this campaign. Please upgrade your plan.', 403);
        }

        $validated = $request->validate($this->leadInventory->leadValidationRules());

        $lead = $campaign->leads()->create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'status' => $validated['status'],
            'source' => 'manual',
            'custom_fields' => $this->leadInventory->mergeProjectNameFromUnit(
                $validated['custom_fields'] ?? [],
                $validated['inventory_unit_id'] ?? null
            ),
            'inventory_unit_id' => $validated['inventory_unit_id'] ?? null,
        ]);

        try {
            $this->leadInventory->sync(
                $lead,
                $validated['inventory_unit_id'] ?? null,
                null,
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            $lead->delete();

            return $this->error($e->getMessage(), 422);
        }

        if (!empty($validated['note'])) {
            $lead->addNote($validated['note'], $request->user()->id);
        }

        $lead->load(['campaign', 'inventoryUnit.floor.tower.project', 'notes.user']);

        return $this->success(new LeadResource($lead), 'Lead created.', 201);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);

        $lead->load([
            'notes.user',
            'campaign',
            'inventoryUnit.floor.wing',
            'inventoryUnit.floor.tower.project',
        ]);

        return $this->success(new LeadResource($lead));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate($this->leadInventory->leadValidationRules());

        $previousUnitId = $lead->inventory_unit_id;

        $lead->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'status' => $validated['status'],
            'custom_fields' => $this->leadInventory->mergeProjectNameFromUnit(
                $validated['custom_fields'] ?? [],
                $validated['inventory_unit_id'] ?? null
            ),
            'inventory_unit_id' => $validated['inventory_unit_id'] ?? null,
        ]);

        try {
            $this->leadInventory->sync(
                $lead->fresh(),
                $validated['inventory_unit_id'] ?? null,
                $previousUnitId,
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $lead->load(['campaign', 'inventoryUnit.floor.tower.project', 'notes.user']);

        return $this->success(new LeadResource($lead), 'Lead updated.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorize('updateStatus', $lead);

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
        ]);

        $lead->update(['status' => $validated['status']]);

        $this->availabilityService->syncFromLeadStatus($lead->fresh(), $request->user());
        $lead->addNote('Status updated to ' . ucfirst($validated['status']), $request->user()->id);

        $lead->load(['campaign', 'inventoryUnit.floor.tower.project']);

        return $this->success(new LeadResource($lead), 'Status updated.');
    }

    public function addNote(Request $request, Lead $lead)
    {
        $this->authorize('addNote', $lead);

        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $note = $lead->addNote($validated['note'], $request->user()->id);
        $note->load('user');

        return $this->success([
            'note' => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at?->toIso8601String(),
            ],
        ], 'Note added.', 201);
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return $this->success(null, 'Lead deleted.');
    }

    public function resendMetaCapi(Lead $lead)
    {
        $this->authorize('update', $lead);

        if (empty($lead->meta_lead_id)) {
            return $this->error('This lead has no Meta lead ID.', 422);
        }

        event(new \App\Events\LeadStatusUpdated($lead));

        return $this->success(null, 'Meta event resent successfully.');
    }
}
