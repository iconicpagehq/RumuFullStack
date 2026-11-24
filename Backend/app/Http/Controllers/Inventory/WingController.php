<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryTower;
use App\Models\InventoryWing;
use Illuminate\Http\Request;

class WingController extends Controller
{
    public function store(Request $request, InventoryTower $tower)
    {
        $this->authorize('create', [InventoryWing::class, $tower]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tower->wings()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Wing added successfully.');
    }

    public function update(Request $request, InventoryWing $wing)
    {
        $this->authorize('update', $wing);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $wing->update($validated);

        return back()->with('success', 'Wing updated successfully.');
    }

    public function destroy(InventoryWing $wing)
    {
        $this->authorize('delete', $wing);

        $wing->delete();

        return back()->with('success', 'Wing deleted successfully.');
    }
}
