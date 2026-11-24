<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use Illuminate\Http\Request;

class TowerController extends Controller
{
    public function store(Request $request, InventoryProject $project)
    {
        $this->authorize('create', [InventoryTower::class, $project]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $project->towers()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Tower added successfully.');
    }

    public function update(Request $request, InventoryTower $tower)
    {
        $this->authorize('update', $tower);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tower->update($validated);

        return back()->with('success', 'Tower updated successfully.');
    }

    public function destroy(InventoryTower $tower)
    {
        $this->authorize('delete', $tower);

        $tower->delete();

        return back()->with('success', 'Tower deleted successfully.');
    }
}
