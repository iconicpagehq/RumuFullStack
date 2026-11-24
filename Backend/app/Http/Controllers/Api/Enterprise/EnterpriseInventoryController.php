<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\InventoryProjectResource;
use App\Http\Resources\InventoryUnitResource;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\InventoryUnitStatusLog;
use App\Models\InventoryWing;
use App\Models\Lead;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnterpriseInventoryController extends EnterpriseApiController
{
    public function projects(Request $request)
    {
        $projects = InventoryProject::where('enterprise_id', $this->enterprise($request)->id)
            ->withCount('towers')
            ->latest()
            ->get()
            ->each(fn (InventoryProject $project) => $project->summary = $project->availabilitySummary());

        return $this->success(InventoryProjectResource::collection($projects));
    }

    public function storeProject(
        Request $request,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $validated = $this->validateProject($request);
        $enterprise = $this->enterprise($request);

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $counter = 1;
        while (InventoryProject::where('enterprise_id', $enterprise->id)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        $project = InventoryProject::create([
            ...$validated,
            'user_id' => $enterprise->owner_id ?? $request->user()->id,
            'enterprise_id' => $enterprise->id,
            'slug' => $slug,
        ]);

        $audit->log($enterprise, $request->user(), 'inventory.project_created', $project, 'Project created');

        $project->summary = $project->availabilitySummary();

        return $this->success(new InventoryProjectResource($project), 'Project created.', 201);
    }

    public function showProject(Request $request, InventoryProject $project)
    {
        $this->ensureProject($request, $project);

        $project->load([
            'towers.wings',
            'towers.floors.wing',
            'towers.floors.units.floor.tower.project',
        ]);
        $project->summary = $project->availabilitySummary();

        return $this->success(new InventoryProjectResource($project));
    }

    public function updateProject(
        Request $request,
        InventoryProject $project,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureProject($request, $project);

        $project->update($this->validateProject($request));

        $audit->log($this->enterprise($request), $request->user(), 'inventory.project_updated', $project);
        $project->summary = $project->availabilitySummary();

        return $this->success(new InventoryProjectResource($project), 'Project updated.');
    }

    public function destroyProject(
        Request $request,
        InventoryProject $project,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureProject($request, $project);

        $blocking = InventoryUnit::query()->forEnterprise($this->enterprise($request)->id)
            ->whereHas('floor.tower', fn ($query) => $query->where('inventory_project_id', $project->id))
            ->whereIn('availability_status', [InventoryUnit::STATUS_BOOKED, InventoryUnit::STATUS_SOLD])
            ->exists();

        if ($blocking) {
            return $this->error('This project has booked or sold units. Resolve those units before deleting the project.', 422);
        }

        $project->delete();
        $audit->log($this->enterprise($request), $request->user(), 'inventory.project_deleted', $project);

        return $this->success(null, 'Project deleted.');
    }

    public function storeTower(Request $request, InventoryProject $project, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureProject($request, $project);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tower = $project->towers()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $audit->log($this->enterprise($request), $request->user(), 'inventory.tower_created', $tower);

        return $this->success(['id' => $tower->id, 'name' => $tower->name], 'Tower created.', 201);
    }

    public function storeWing(Request $request, InventoryTower $tower, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureTower($request, $tower);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $wing = $tower->wings()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $audit->log($this->enterprise($request), $request->user(), 'inventory.wing_created', $wing);

        return $this->success(['id' => $wing->id, 'name' => $wing->name], 'Wing created.', 201);
    }

    public function updateWing(Request $request, InventoryWing $wing, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureWing($request, $wing);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $wing->update($validated);
        $audit->log($this->enterprise($request), $request->user(), 'inventory.wing_updated', $wing);

        return $this->success(['id' => $wing->id, 'name' => $wing->name], 'Wing updated.');
    }

    public function destroyWing(Request $request, InventoryWing $wing, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureWing($request, $wing);

        if ($wing->floors()->whereHas('units', fn ($q) => $q->whereIn('availability_status', ['booked', 'sold']))->exists()) {
            return $this->error('This wing has booked or sold units and cannot be deleted.', 422);
        }

        $wing->delete();
        $audit->log($this->enterprise($request), $request->user(), 'inventory.wing_deleted', $wing);

        return $this->success(null, 'Wing deleted.');
    }

    public function updateTower(Request $request, InventoryTower $tower, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureTower($request, $tower);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tower->update($validated);
        $audit->log($this->enterprise($request), $request->user(), 'inventory.tower_updated', $tower);

        return $this->success(['id' => $tower->id, 'name' => $tower->name], 'Tower updated.');
    }

    public function destroyTower(Request $request, InventoryTower $tower, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureTower($request, $tower);

        if ($tower->floors()->whereHas('units', fn ($q) => $q->whereIn('availability_status', ['booked', 'sold']))->exists()) {
            return $this->error('This tower has booked or sold units and cannot be deleted.', 422);
        }

        $tower->delete();
        $audit->log($this->enterprise($request), $request->user(), 'inventory.tower_deleted', $tower);

        return $this->success(null, 'Tower deleted.');
    }

    public function storeFloor(Request $request, InventoryTower $tower, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureTower($request, $tower);

        $validated = $request->validate([
            'floor_number' => 'required|integer',
            'inventory_wing_id' => 'nullable|integer',
        ]);

        if (!empty($validated['inventory_wing_id'])) {
            $wing = InventoryWing::where('inventory_tower_id', $tower->id)->find($validated['inventory_wing_id']);
            if (!$wing) {
                throw ValidationException::withMessages(['inventory_wing_id' => ['Select a wing from this tower.']]);
            }
        }

        $exists = InventoryFloor::where('inventory_tower_id', $tower->id)
            ->where('inventory_wing_id', $validated['inventory_wing_id'] ?? null)
            ->where('floor_number', $validated['floor_number'])
            ->exists();

        if ($exists) {
            return $this->error('This floor already exists for the selected tower and wing.', 409);
        }

        $floor = $tower->floors()->create([
            'inventory_wing_id' => $validated['inventory_wing_id'] ?? null,
            'floor_number' => $validated['floor_number'],
        ]);

        $audit->log($this->enterprise($request), $request->user(), 'inventory.floor_created', $floor);

        return $this->success([
            'id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'label' => $floor->displayLabel(),
        ], 'Floor created.', 201);
    }

    public function updateFloor(Request $request, InventoryFloor $floor, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureFloor($request, $floor);

        $validated = $request->validate(['floor_number' => 'required|integer']);
        $floor->update($validated);

        $audit->log($this->enterprise($request), $request->user(), 'inventory.floor_updated', $floor);

        return $this->success(['id' => $floor->id, 'floor_number' => $floor->floor_number], 'Floor updated.');
    }

    public function destroyFloor(Request $request, InventoryFloor $floor, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureFloor($request, $floor);

        if ($floor->units()->whereIn('availability_status', ['booked', 'sold'])->exists()) {
            return $this->error('This floor has booked or sold units and cannot be deleted.', 422);
        }

        $floor->delete();
        $audit->log($this->enterprise($request), $request->user(), 'inventory.floor_deleted', $floor);

        return $this->success(null, 'Floor deleted.');
    }

    public function storeUnit(
        Request $request,
        InventoryFloor $floor,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureFloor($request, $floor);
        $limits->ensure($this->enterprise($request), 'inventory_units');

        $validated = $this->validateUnit($request);

        $unit = $floor->units()->create([
            ...$validated,
            'availability_status' => InventoryUnit::STATUS_AVAILABLE,
        ]);
        $unit->load('floor.tower.project');

        $audit->log($this->enterprise($request), $request->user(), 'inventory.unit_created', $unit, null, [
            'project_id' => $floor->tower?->inventory_project_id,
        ]);

        return $this->success(new InventoryUnitResource($unit), 'Unit created.', 201);
    }

    public function bulkStoreUnits(
        Request $request,
        InventoryFloor $floor,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureFloor($request, $floor);

        $validated = $request->validate([
            'prefix' => 'nullable|string|max:20',
            'start_number' => 'required|integer|min:1',
            'end_number' => 'required|integer|gte:start_number',
            'layout' => 'nullable|in:' . implode(',', InventoryUnit::LAYOUTS),
            'carpet_area' => 'nullable|numeric|min:0',
            'built_up_area' => 'nullable|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $count = $validated['end_number'] - $validated['start_number'] + 1;
        if ($count > 500) {
            throw ValidationException::withMessages(['end_number' => ['Create at most 500 units per batch.']]);
        }

        $limits->ensure($this->enterprise($request), 'inventory_units', $count);

        $created = 0;
        $prefix = $validated['prefix'] ?? '';

        DB::transaction(function () use ($floor, $validated, $prefix, &$created) {
            for ($number = $validated['start_number']; $number <= $validated['end_number']; $number++) {
                $unitNumber = $prefix . $number;

                if ($floor->units()->where('unit_number', $unitNumber)->exists()) {
                    continue;
                }

                $floor->units()->create([
                    'unit_number' => $unitNumber,
                    'layout' => $validated['layout'] ?? null,
                    'carpet_area' => $validated['carpet_area'] ?? null,
                    'built_up_area' => $validated['built_up_area'] ?? null,
                    'base_price' => $validated['base_price'] ?? null,
                    'availability_status' => InventoryUnit::STATUS_AVAILABLE,
                ]);

                $created++;
            }
        });

        $audit->log($this->enterprise($request), $request->user(), 'inventory.units_bulk_created', $floor, null, [
            'created' => $created,
        ]);

        return $this->success(['created' => $created], 'Units created.', 201);
    }

    public function updateUnit(Request $request, InventoryUnit $unit, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureUnit($request, $unit);

        $validated = $request->validate([
            'unit_number' => 'sometimes|required|string|max:50',
            'layout' => 'nullable|in:' . implode(',', InventoryUnit::LAYOUTS),
            'carpet_area' => 'nullable|numeric|min:0',
            'built_up_area' => 'nullable|numeric|min:0',
            'facing' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $unit->update($validated);
        $unit->load('floor.tower.project');

        $audit->log($this->enterprise($request), $request->user(), 'inventory.unit_updated', $unit);

        return $this->success(new InventoryUnitResource($unit), 'Unit updated.');
    }

    public function updateUnitStatus(Request $request, InventoryUnit $unit, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin', 'manager', 'employee']);
        $this->ensureUnit($request, $unit);
        $membership = $this->membership($request);

        $validated = $request->validate([
            'availability_status' => 'required|in:' . implode(',', InventoryUnit::STATUSES),
            'lead_id' => 'nullable|integer',
            'note' => 'nullable|string|max:1000',
        ]);

        $lead = null;
        if (!empty($validated['lead_id'])) {
            $lead = Lead::where('enterprise_id', $this->enterprise($request)->id)->find($validated['lead_id']);
            if (!$lead) {
                throw ValidationException::withMessages(['lead_id' => ['Select a lead from this enterprise.']]);
            }
        }

        $newStatus = $validated['availability_status'];

        if ($membership->isEmployee()) {
            if (!in_array($newStatus, ['hold', 'booked'], true)) {
                abort(403, 'Employees can only reserve inventory for their assigned leads.');
            }

            if (!$lead || (int) $lead->assigned_to !== (int) $membership->user_id) {
                abort(403, 'You can only reserve inventory for a lead assigned to you.');
            }
        }

        if ($membership->isManager()) {
            if (!in_array($newStatus, ['available', 'hold', 'booked'], true)) {
                abort(403, 'Managers can only reserve or release inventory for their team.');
            }

            $teamIds = $this->teamScopeIds($request);
            $unitLead = $unit->lead_id
                ? Lead::where('enterprise_id', $this->enterprise($request)->id)->find($unit->lead_id)
                : null;

            if ($unitLead && !in_array((int) $unitLead->team_id, $teamIds, true)
                && (int) $unitLead->assigned_manager_id !== (int) $membership->user_id) {
                abort(403, 'This unit is reserved by another team.');
            }

            if (in_array($newStatus, ['hold', 'booked'], true)) {
                if (!$lead) {
                    throw ValidationException::withMessages(['lead_id' => ['Select a lead from your team to reserve this unit.']]);
                }

                if (!in_array((int) $lead->team_id, $teamIds, true)
                    && (int) $lead->assigned_manager_id !== (int) $membership->user_id) {
                    abort(403, 'This lead is outside your team.');
                }
            }
        }

        $updated = DB::transaction(function () use ($unit, $validated, $lead, $request) {
            $unit = InventoryUnit::whereKey($unit->id)->lockForUpdate()->firstOrFail();
            $newStatus = $validated['availability_status'];

            if ($unit->availability_status === $newStatus) {
                return $unit;
            }

            $allowed = [
                'available' => ['hold', 'booked'],
                'hold' => ['available', 'booked'],
                'booked' => ['available', 'sold'],
                'sold' => [],
            ];

            if (!in_array($newStatus, $allowed[$unit->availability_status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'availability_status' => ["A {$unit->availability_status} unit cannot be changed to {$newStatus}."],
                ]);
            }

            if ($unit->lead_id && in_array($newStatus, ['booked', 'sold'], true) && $lead && (int) $unit->lead_id !== (int) $lead->id) {
                throw ValidationException::withMessages([
                    'lead_id' => ['This unit is already linked to another lead.'],
                ]);
            }

            $previous = $unit->availability_status;
            $unit->update([
                'availability_status' => $newStatus,
                'lead_id' => in_array($newStatus, ['available', 'hold'], true) ? null : ($lead?->id ?? $unit->lead_id),
            ]);

            InventoryUnitStatusLog::create([
                'inventory_unit_id' => $unit->id,
                'old_status' => $previous,
                'new_status' => $newStatus,
                'changed_by' => $request->user()->id,
                'lead_id' => $unit->lead_id,
                'note' => $validated['note'] ?? null,
                'created_at' => now(),
            ]);

            return $unit;
        });

        $updated->load('floor.tower.project');
        $audit->log($this->enterprise($request), $request->user(), 'inventory.unit_status_changed', $updated, null, [
            'status' => $updated->availability_status,
        ]);

        return $this->success(new InventoryUnitResource($updated), 'Unit status updated.');
    }

    public function destroyUnit(Request $request, InventoryUnit $unit, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureUnit($request, $unit);

        if (in_array($unit->availability_status, ['booked', 'sold'], true)) {
            return $this->error('Booked or sold units cannot be deleted.', 422);
        }

        $unit->delete();
        $audit->log($this->enterprise($request), $request->user(), 'inventory.unit_deleted', $unit);

        return $this->success(null, 'Unit deleted.');
    }

    public function floorUnits(Request $request, InventoryFloor $floor)
    {
        $this->ensureFloor($request, $floor);

        $query = $floor->units()->with('floor.tower.project');

        if ($status = $request->input('status')) {
            $query->where('availability_status', $status);
        }

        return $this->success(InventoryUnitResource::collection(
            $query->orderBy('unit_number')->get()
        ));
    }

    public function searchUnits(Request $request)
    {
        $query = InventoryUnit::query()
            ->forEnterprise($this->enterprise($request)->id)
            ->with('floor.tower.project')
            ->where(function ($inner) use ($request) {
                $search = $request->input('search');
                $inner->where('unit_number', 'like', "%{$search}%")
                    ->orWhereHas('floor.tower.project', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });

        if ($status = $request->input('status')) {
            $query->where('availability_status', $status);
        }

        if ($projectId = $request->input('project_id')) {
            $query->whereHas('floor.tower', fn ($q) => $q->where('inventory_project_id', $projectId));
        }

        return $this->success(InventoryUnitResource::collection(
            $query->orderBy('unit_number')->limit(50)->get()
        ));
    }

    private function validateProject(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive',
        ]);
    }

    private function validateUnit(Request $request): array
    {
        return $request->validate([
            'unit_number' => 'required|string|max:50',
            'layout' => 'nullable|in:' . implode(',', InventoryUnit::LAYOUTS),
            'carpet_area' => 'nullable|numeric|min:0',
            'built_up_area' => 'nullable|numeric|min:0',
            'facing' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
    }

    private function ensureProject(Request $request, InventoryProject $project): void
    {
        abort_if($project->enterprise_id !== $this->enterprise($request)->id, 404);
    }

    private function ensureTower(Request $request, InventoryTower $tower): void
    {
        abort_if($tower->project?->enterprise_id !== $this->enterprise($request)->id, 404);
    }

    private function ensureFloor(Request $request, InventoryFloor $floor): void
    {
        abort_if($floor->tower?->project?->enterprise_id !== $this->enterprise($request)->id, 404);
    }

    private function ensureWing(Request $request, InventoryWing $wing): void
    {
        abort_if($wing->tower?->project?->enterprise_id !== $this->enterprise($request)->id, 404);
    }

    private function ensureUnit(Request $request, InventoryUnit $unit): void
    {
        abort_if($unit->floor?->tower?->project?->enterprise_id !== $this->enterprise($request)->id, 404);
    }
}
