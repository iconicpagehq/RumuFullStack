<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryUnit;
use App\Services\InventoryAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UnitController extends Controller
{
    public function __construct(
        private InventoryAvailabilityService $availabilityService
    ) {}

    public function store(Request $request, InventoryFloor $floor)
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

        $floor->units()->create($validated);

        return back()->with('success', 'Unit added successfully.');
    }

    public function bulkStore(Request $request, InventoryFloor $floor)
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

        return back()->with('success', "{$created} units created successfully.");
    }

    public function update(Request $request, InventoryUnit $unit)
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

        return back()->with('success', 'Unit updated successfully.');
    }

    public function updateStatus(Request $request, InventoryUnit $unit)
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'availability_status' => 'required|in:' . implode(',', InventoryUnit::STATUSES),
            'note' => 'nullable|string|max:500',
        ]);

        $this->availabilityService->updateStatus(
            $unit,
            $validated['availability_status'],
            Auth::user(),
            $validated['note'] ?? null
        );

        return back()->with('success', 'Unit status updated.');
    }

    public function destroy(InventoryUnit $unit)
    {
        $this->authorize('delete', $unit);

        if ($unit->layout_image) {
            Storage::disk('public')->delete($unit->layout_image);
        }

        $unit->delete();

        return back()->with('success', 'Unit deleted successfully.');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:inventory_projects,id',
            'q' => 'nullable|string|max:100',
        ]);

        $query = InventoryUnit::query()
            ->with(['floor.wing', 'floor.tower.project'])
            ->availableForAssignment();

        if (Auth::user()->role === 'realtor') {
            $query->byUser(Auth::id());
        }

        if (!empty($validated['project_id'])) {
            $query->whereHas('floor.tower', fn ($q) => $q->where('inventory_project_id', $validated['project_id']));
        }

        if (!empty($validated['q'])) {
            $q = $validated['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('unit_number', 'like', "%{$q}%")
                    ->orWhereHas('floor.tower.project', fn ($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }

        $units = $query->limit(50)->get()->map(fn (InventoryUnit $unit) => [
            'id' => $unit->id,
            'label' => $unit->fullPath(),
            'layout' => $unit->layout,
            'status' => $unit->availability_status,
            'price' => $unit->base_price,
        ]);

        return response()->json($units);
    }
}
