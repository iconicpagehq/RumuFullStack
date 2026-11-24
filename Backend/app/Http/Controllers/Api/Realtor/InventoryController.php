<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\InventoryProjectResource;
use App\Http\Resources\InventoryUnitResource;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\InventoryWing;
use App\Models\Lead;
use App\Services\InventoryAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InventoryController extends ApiController
{
    public function __construct(
        private InventoryAvailabilityService $availabilityService
    ) {}

    public function projects(Request $request)
    {
        $this->authorize('viewAny', InventoryProject::class);

        $projects = InventoryProject::query()
            ->where('user_id', $request->user()->id)
            ->withCount('towers')
            ->latest()
            ->get()
            ->map(function (InventoryProject $project) {
                $project->summary = $project->availabilitySummary();

                return $project;
            });

        return $this->success(InventoryProjectResource::collection($projects));
    }

    public function storeProject(Request $request)
    {
        $this->authorize('create', InventoryProject::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive',
        ]);

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $counter = 1;
        while (InventoryProject::where('user_id', $request->user()->id)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $project = InventoryProject::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'slug' => $slug,
        ]);

        $project->summary = $project->availabilitySummary();

        return $this->success(new InventoryProjectResource($project), 'Project created.', 201);
    }

    public function showProject(InventoryProject $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'towers.wings',
            'towers.floors.wing',
            'towers.floors.units',
        ]);
        $project->summary = $project->availabilitySummary();

        return $this->success(new InventoryProjectResource($project));
    }

    public function updateProject(Request $request, InventoryProject $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive',
        ]);

        $project->update($validated);
        $project->summary = $project->availabilitySummary();

        return $this->success(new InventoryProjectResource($project), 'Project updated.');
    }

    public function destroyProject(InventoryProject $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return $this->success(null, 'Project deleted.');
    }

    public function storeTower(Request $request, InventoryProject $project)
    {
        $this->authorize('create', [InventoryTower::class, $project]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tower = $project->towers()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->success([
            'id' => $tower->id,
            'name' => $tower->name,
            'sort_order' => $tower->sort_order,
        ], 'Tower created.', 201);
    }

    public function updateTower(Request $request, InventoryTower $tower)
    {
        $this->authorize('update', $tower);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tower->update($validated);

        return $this->success([
            'id' => $tower->id,
            'name' => $tower->name,
            'sort_order' => $tower->sort_order,
        ], 'Tower updated.');
    }

    public function destroyTower(InventoryTower $tower)
    {
        $this->authorize('delete', $tower);

        $tower->delete();

        return $this->success(null, 'Tower deleted.');
    }

    public function storeWing(Request $request, InventoryTower $tower)
    {
        $this->authorize('create', [InventoryWing::class, $tower]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $wing = $tower->wings()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->success([
            'id' => $wing->id,
            'name' => $wing->name,
            'sort_order' => $wing->sort_order,
            'inventory_tower_id' => $wing->inventory_tower_id,
        ], 'Wing created.', 201);
    }

    public function updateWing(Request $request, InventoryWing $wing)
    {
        $this->authorize('update', $wing);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $wing->update($validated);

        return $this->success([
            'id' => $wing->id,
            'name' => $wing->name,
            'sort_order' => $wing->sort_order,
            'inventory_tower_id' => $wing->inventory_tower_id,
        ], 'Wing updated.');
    }

    public function destroyWing(InventoryWing $wing)
    {
        $this->authorize('delete', $wing);

        $wing->delete();

        return $this->success(null, 'Wing deleted.');
    }

    public function storeFloorOnTower(Request $request, InventoryTower $tower)
    {
        $this->authorize('create', [InventoryFloor::class, $tower]);

        $validated = $request->validate([
            'floor_number' => 'required|integer',
            'inventory_wing_id' => 'nullable|exists:inventory_wings,id',
            'layout_image' => 'nullable|image|max:4096',
        ]);

        if (!empty($validated['inventory_wing_id'])) {
            $wing = InventoryWing::findOrFail($validated['inventory_wing_id']);
            if ($wing->inventory_tower_id !== $tower->id) {
                return $this->error('Selected wing does not belong to this tower.', 422);
            }
        }

        $exists = InventoryFloor::where('inventory_tower_id', $tower->id)
            ->where('inventory_wing_id', $validated['inventory_wing_id'] ?? null)
            ->where('floor_number', $validated['floor_number'])
            ->exists();

        if ($exists) {
            return $this->error('This floor already exists for the selected tower/wing.', 409);
        }

        $data = [
            'inventory_tower_id' => $tower->id,
            'inventory_wing_id' => $validated['inventory_wing_id'] ?? null,
            'floor_number' => $validated['floor_number'],
        ];

        if ($request->hasFile('layout_image')) {
            $data['layout_image'] = $request->file('layout_image')->store('inventory/layouts', 'public');
        }

        $floor = $tower->floors()->create($data);
        $floor->load(['wing', 'tower.project']);

        return $this->success([
            'id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'label' => $floor->displayLabel(),
            'inventory_tower_id' => $floor->inventory_tower_id,
            'inventory_wing_id' => $floor->inventory_wing_id,
            'layout_image' => $floor->layout_image,
        ], 'Floor created.', 201);
    }

    public function storeFloorOnWing(Request $request, InventoryWing $wing)
    {
        $tower = $wing->tower;
        $request->merge(['inventory_wing_id' => $wing->id]);

        return $this->storeFloorOnTower($request, $tower);
    }

    public function updateFloor(Request $request, InventoryFloor $floor)
    {
        $this->authorize('update', $floor);

        $validated = $request->validate([
            'floor_number' => 'required|integer',
            'layout_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('layout_image')) {
            if ($floor->layout_image) {
                Storage::disk('public')->delete($floor->layout_image);
            }
            $validated['layout_image'] = $request->file('layout_image')->store('inventory/layouts', 'public');
        }

        $floor->update($validated);

        return $this->success([
            'id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'label' => $floor->loadMissing('wing')->displayLabel(),
            'inventory_tower_id' => $floor->inventory_tower_id,
            'inventory_wing_id' => $floor->inventory_wing_id,
            'layout_image' => $floor->layout_image,
        ], 'Floor updated.');
    }

    public function destroyFloor(InventoryFloor $floor)
    {
        $this->authorize('delete', $floor);

        if ($floor->layout_image) {
            Storage::disk('public')->delete($floor->layout_image);
        }

        $floor->delete();

        return $this->success(null, 'Floor deleted.');
    }

    public function storeUnit(Request $request, InventoryFloor $floor)
    {
        $this->authorize('create', [InventoryUnit::class, $floor]);

        $validated = $request->validate([
            'unit_number' => 'required|string|max:50',
            'layout' => 'nullable|in:' . implode(',', InventoryUnit::LAYOUTS),
            'carpet_area' => 'nullable|numeric|min:0',
            'built_up_area' => 'nullable|numeric|min:0',
            'facing' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'layout_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('layout_image')) {
            $validated['layout_image'] = $request->file('layout_image')->store('inventory/layouts', 'public');
        }

        $unit = $floor->units()->create($validated);
        $unit->load(['floor.wing', 'floor.tower.project']);

        return $this->success(new InventoryUnitResource($unit), 'Unit created.', 201);
    }

    public function bulkStoreUnits(Request $request, InventoryFloor $floor)
    {
        $this->authorize('create', [InventoryUnit::class, $floor]);

        $validated = $request->validate([
            'prefix' => 'nullable|string|max:20',
            'start_number' => 'required|integer|min:1',
            'end_number' => 'required|integer|gte:start_number',
            'layout' => 'nullable|in:' . implode(',', InventoryUnit::LAYOUTS),
            'carpet_area' => 'nullable|numeric|min:0',
            'built_up_area' => 'nullable|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $created = 0;
        $prefix = $validated['prefix'] ?? '';

        for ($i = $validated['start_number']; $i <= $validated['end_number']; $i++) {
            $unitNumber = $prefix . $i;

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

        return $this->success(['created' => $created], 'Units created.', 201);
    }

    public function updateUnit(Request $request, InventoryUnit $unit)
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'unit_number' => 'required|string|max:50',
            'layout' => 'nullable|in:' . implode(',', InventoryUnit::LAYOUTS),
            'carpet_area' => 'nullable|numeric|min:0',
            'built_up_area' => 'nullable|numeric|min:0',
            'facing' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'layout_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('layout_image')) {
            if ($unit->layout_image) {
                Storage::disk('public')->delete($unit->layout_image);
            }
            $validated['layout_image'] = $request->file('layout_image')->store('inventory/layouts', 'public');
        }

        $unit->update($validated);
        $unit->load(['floor.wing', 'floor.tower.project']);

        return $this->success(new InventoryUnitResource($unit), 'Unit updated.');
    }

    
    public function assignUnitLead(Request $request, InventoryUnit $unit)
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'lead_id' => 'nullable|integer|exists:leads,id',
            'action' => 'nullable|in:hold,booked,sold,available',
            'note' => 'nullable|string|max:500',
        ]);

        if (empty($validated['lead_id'])) {
            $this->availabilityService->releaseUnit(
                $unit,
                $request->user(),
                $validated['note'] ?? 'Lead unassigned from unit'
            );
        } else {
            $lead = Lead::query()
                ->forRealtor($request->user()->id)
                ->findOrFail($validated['lead_id']);

            $this->availabilityService->assignToLead($unit, $lead, $request->user());

            if (!empty($validated['action']) && $validated['action'] !== 'available') {
                $this->availabilityService->updateStatus(
                    $unit,
                    $validated['action'],
                    $request->user(),
                    $validated['note'] ?? 'Status updated with lead assignment'
                );
            }
        }

        $unit->refresh()->load(['floor.wing', 'floor.tower.project', 'lead']);

        return $this->success(new InventoryUnitResource($unit), 'Unit lead assignment updated.');
    }

    public function updateUnitStatus(Request $request, InventoryUnit $unit)
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'availability_status' => 'required|in:' . implode(',', InventoryUnit::STATUSES),
            'note' => 'nullable|string|max:500',
        ]);

        $this->availabilityService->updateStatus(
            $unit,
            $validated['availability_status'],
            $request->user(),
            $validated['note'] ?? null
        );

        $unit->refresh()->load(['floor.wing', 'floor.tower.project']);

        return $this->success(new InventoryUnitResource($unit), 'Unit status updated.');
    }

    public function destroyUnit(InventoryUnit $unit)
    {
        $this->authorize('delete', $unit);

        if ($unit->layout_image) {
            Storage::disk('public')->delete($unit->layout_image);
        }

        $unit->delete();

        return $this->success(null, 'Unit deleted.');
    }

    public function floorUnits(Request $request, InventoryFloor $floor)
    {
        $this->authorize('view', $floor);

        $statusFilter = $request->get('status');
        $layoutFilter = $request->get('layout');

        $query = $floor->units()->with(['floor.wing', 'floor.tower.project', 'lead']);

        if ($statusFilter && in_array($statusFilter, InventoryUnit::STATUSES, true)) {
            $query->where('availability_status', $statusFilter);
        }

        if ($layoutFilter && in_array($layoutFilter, InventoryUnit::LAYOUTS, true)) {
            $query->where('layout', $layoutFilter);
        }

        $units = $query->orderBy('unit_number')->paginate(50);

        return $this->paginated(InventoryUnitResource::collection($units));
    }

    public function searchUnits(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:inventory_projects,id',
            'q' => 'nullable|string|max:100',
        ]);

        $query = InventoryUnit::query()
            ->with(['floor.wing', 'floor.tower.project', 'lead'])
            ->availableForAssignment()
            ->byUser($request->user()->id);

        if (!empty($validated['project_id'])) {
            $project = InventoryProject::findOrFail($validated['project_id']);
            $this->authorize('view', $project);
            $query->whereHas('floor.tower', fn ($q) => $q->where('inventory_project_id', $validated['project_id']));
        }

        if (!empty($validated['q'])) {
            $q = $validated['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('unit_number', 'like', "%{$q}%")
                    ->orWhereHas('floor.tower.project', fn ($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }

        $units = $query->limit(50)->get();

        return $this->success(InventoryUnitResource::collection($units));
    }
}
