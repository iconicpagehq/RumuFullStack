<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', InventoryProject::class);

        $query = InventoryProject::query()->withCount('towers');

        if (Auth::user()->role === 'realtor') {
            $query->where('user_id', Auth::id());
        }

        $projects = $query->latest()->get()->map(function (InventoryProject $project) {
            $project->summary = $project->availabilitySummary();
            return $project;
        });

        return view('inventory.projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', InventoryProject::class);

        return view('inventory.projects.create');
    }

    public function store(Request $request)
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
        while (InventoryProject::where('user_id', Auth::id())->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $project = InventoryProject::create([
            ...$validated,
            'user_id' => Auth::id(),
            'slug' => $slug,
        ]);

        return redirect()->route('inventory.projects.show', $project)
            ->with('success', 'Inventory project created successfully.');
    }

    public function show(Request $request, InventoryProject $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'towers.wings',
            'towers.floors.wing',
            'towers.floors.units',
        ]);

        $summary = $project->availabilitySummary();

        $selectedFloorId = $request->integer('floor') ?: null;
        $statusFilter = $request->get('status');
        $layoutFilter = $request->get('layout');

        $unitsQuery = $project->getUnitsQuery()->with(['floor.wing', 'floor.tower', 'lead']);

        if ($selectedFloorId) {
            $unitsQuery->where('inventory_floor_id', $selectedFloorId);
        }

        if ($statusFilter && in_array($statusFilter, \App\Models\InventoryUnit::STATUSES, true)) {
            $unitsQuery->where('availability_status', $statusFilter);
        }

        if ($layoutFilter && in_array($layoutFilter, \App\Models\InventoryUnit::LAYOUTS, true)) {
            $unitsQuery->where('layout', $layoutFilter);
        }

        $units = $unitsQuery->orderBy('unit_number')->get();

        return view('inventory.projects.show', compact(
            'project',
            'summary',
            'units',
            'selectedFloorId',
            'statusFilter',
            'layoutFilter'
        ));
    }

    public function edit(InventoryProject $project)
    {
        $this->authorize('update', $project);

        return view('inventory.projects.edit', compact('project'));
    }

    public function update(Request $request, InventoryProject $project)
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

        return redirect()->route('inventory.projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(InventoryProject $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('inventory.projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
