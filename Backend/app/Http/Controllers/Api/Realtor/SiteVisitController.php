<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Models\InventoryProject;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\RealtorSiteVisit;
use Illuminate\Http\Request;

class SiteVisitController extends ApiController
{
    public function index(Request $request)
    {
        $query = RealtorSiteVisit::forUser($request->user()->id)
            ->with(['lead:id,name,phone', 'project:id,name', 'unit:id,unit_number']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($leadId = $request->input('lead_id')) {
            $query->where('lead_id', $leadId);
        }

        $statuses = RealtorSiteVisit::forUser($request->user()->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $paginator = $query->orderByRaw('CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_at')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (RealtorSiteVisit $visit) => $this->present($visit)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'status_counts' => (object) $statuses->all(),
                'upcoming_count' => RealtorSiteVisit::forUser($request->user()->id)
                    ->where('status', RealtorSiteVisit::STATUS_SCHEDULED)
                    ->where('scheduled_at', '>=', now())
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateVisit($request);

        $visit = RealtorSiteVisit::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->success($this->present($visit->load(['lead:id,name,phone', 'project:id,name', 'unit:id,unit_number'])), 'Site visit scheduled.', 201);
    }

    public function show(Request $request, RealtorSiteVisit $siteVisit)
    {
        $this->ensureOwned($request, $siteVisit);

        return $this->success($this->present($siteVisit->load(['lead:id,name,phone', 'project:id,name', 'unit:id,unit_number'])));
    }

    public function update(Request $request, RealtorSiteVisit $siteVisit)
    {
        $this->ensureOwned($request, $siteVisit);

        $validated = $this->validateVisit($request);
        $siteVisit->update($validated);

        return $this->success($this->present($siteVisit->fresh()->load(['lead:id,name,phone', 'project:id,name', 'unit:id,unit_number'])), 'Site visit updated.');
    }

    public function updateStatus(Request $request, RealtorSiteVisit $siteVisit)
    {
        $this->ensureOwned($request, $siteVisit);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', RealtorSiteVisit::STATUSES),
            'customer_attended' => 'nullable|boolean',
            'outcome' => 'nullable|string|max:2000',
            'feedback' => 'nullable|string|max:2000',
            'next_action' => 'nullable|string|max:2000',
            'cancellation_reason' => 'nullable|string|max:2000',
        ]);

        $status = $validated['status'];
        $siteVisit->update([
            'status' => $status,
            'customer_attended' => $validated['customer_attended'] ?? $siteVisit->customer_attended,
            'outcome' => $validated['outcome'] ?? $siteVisit->outcome,
            'feedback' => $validated['feedback'] ?? $siteVisit->feedback,
            'next_action' => $validated['next_action'] ?? $siteVisit->next_action,
            'cancellation_reason' => $validated['cancellation_reason'] ?? $siteVisit->cancellation_reason,
            'completed_at' => in_array($status, [RealtorSiteVisit::STATUS_COMPLETED, RealtorSiteVisit::STATUS_NO_SHOW], true)
                ? now()
                : null,
        ]);

        return $this->success($this->present($siteVisit->fresh()->load(['lead:id,name,phone', 'project:id,name', 'unit:id,unit_number'])), 'Site visit status updated.');
    }

    public function destroy(Request $request, RealtorSiteVisit $siteVisit)
    {
        $this->ensureOwned($request, $siteVisit);
        $siteVisit->delete();

        return $this->success(null, 'Site visit deleted.');
    }

    private function validateVisit(Request $request): array
    {
        $validated = $request->validate([
            'lead_id' => 'required|integer',
            'inventory_project_id' => 'nullable|integer',
            'inventory_unit_id' => 'nullable|integer',
            'scheduled_at' => 'required|date',
            'status' => 'sometimes|in:' . implode(',', RealtorSiteVisit::STATUSES),
        ]);

        $ownsLead = Lead::whereNull('enterprise_id')
            ->forRealtor($request->user()->id)
            ->whereKey($validated['lead_id'])
            ->exists();

        abort_if(!$ownsLead, 404);

        if (!empty($validated['inventory_project_id'])) {
            $ownsProject = InventoryProject::byUser($request->user()->id)
                ->whereKey($validated['inventory_project_id'])
                ->exists();
            abort_if(!$ownsProject, 404);
        }

        if (!empty($validated['inventory_unit_id'])) {
            $ownsUnit = InventoryUnit::byUser($request->user()->id)
                ->whereKey($validated['inventory_unit_id'])
                ->exists();
            abort_if(!$ownsUnit, 404);
        }

        $validated['status'] = $validated['status'] ?? RealtorSiteVisit::STATUS_SCHEDULED;

        return $validated;
    }

    private function ensureOwned(Request $request, RealtorSiteVisit $siteVisit): void
    {
        abort_if((int) $siteVisit->user_id !== (int) $request->user()->id, 404);
    }

    private function present(RealtorSiteVisit $visit): array
    {
        return [
            'id' => $visit->id,
            'lead' => $visit->relationLoaded('lead') && $visit->lead ? [
                'id' => $visit->lead->id,
                'name' => $visit->lead->name,
                'phone' => $visit->lead->phone,
            ] : null,
            'project' => $visit->relationLoaded('project') && $visit->project ? [
                'id' => $visit->project->id,
                'name' => $visit->project->name,
            ] : null,
            'unit' => $visit->relationLoaded('unit') && $visit->unit ? [
                'id' => $visit->unit->id,
                'unit_number' => $visit->unit->unit_number,
            ] : null,
            'scheduled_at' => $visit->scheduled_at?->toIso8601String(),
            'status' => $visit->status,
            'customer_attended' => $visit->customer_attended,
            'outcome' => $visit->outcome,
            'feedback' => $visit->feedback,
            'next_action' => $visit->next_action,
            'cancellation_reason' => $visit->cancellation_reason,
            'completed_at' => $visit->completed_at?->toIso8601String(),
            'created_at' => $visit->created_at?->toIso8601String(),
        ];
    }
}
