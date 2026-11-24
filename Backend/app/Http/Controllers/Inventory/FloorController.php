<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryFloor;
use App\Models\InventoryTower;
use App\Models\InventoryWing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FloorController extends Controller
{
    public function storeOnTower(Request $request, InventoryTower $tower)
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
                return back()->with('error', 'Selected wing does not belong to this tower.');
            }
        }

        $exists = InventoryFloor::where('inventory_tower_id', $tower->id)
            ->where('inventory_wing_id', $validated['inventory_wing_id'] ?? null)
            ->where('floor_number', $validated['floor_number'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This floor already exists for the selected tower/wing.');
        }

        $data = [
            'inventory_tower_id' => $tower->id,
            'inventory_wing_id' => $validated['inventory_wing_id'] ?? null,
            'floor_number' => $validated['floor_number'],
        ];

        if ($request->hasFile('layout_image')) {
            $data['layout_image'] = $request->file('layout_image')->store('inventory/layouts', 'public');
        }

        $tower->floors()->create($data);

        return back()->with('success', 'Floor added successfully.');
    }

    public function storeOnWing(Request $request, InventoryWing $wing)
    {
        $tower = $wing->tower;
        $request->merge(['inventory_wing_id' => $wing->id]);

        return $this->storeOnTower($request, $tower);
    }

    public function update(Request $request, InventoryFloor $floor)
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

        return back()->with('success', 'Floor updated successfully.');
    }

    public function destroy(InventoryFloor $floor)
    {
        $this->authorize('delete', $floor);

        if ($floor->layout_image) {
            Storage::disk('public')->delete($floor->layout_image);
        }

        $floor->delete();

        return back()->with('success', 'Floor deleted successfully.');
    }
}
