@if(isset($inventoryProjects) && $inventoryProjects->isNotEmpty())
<div class="mb-8">
    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
        <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
        Inventory Unit
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="inventory_project_filter" class="block text-sm font-semibold text-gray-700 mb-2">Filter by Project</label>
            <select id="inventory_project_filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All projects</option>
                @foreach($inventoryProjects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="inventory_unit_id" class="block text-sm font-semibold text-gray-700 mb-2">Assign Flat / Unit</label>
            <select name="inventory_unit_id" id="inventory_unit_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">No unit assigned</option>
                @if(isset($lead) && $lead->inventoryUnit)
                    <option value="{{ $lead->inventory_unit_id }}" selected>{{ $lead->inventoryUnit->fullPath() }}</option>
                @endif
            </select>
            <p class="text-xs text-gray-500 mt-1">Available and on-hold units from your inventory</p>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectFilter = document.getElementById('inventory_project_filter');
    const unitSelect = document.getElementById('inventory_unit_id');
    const selectedUnit = '{{ old('inventory_unit_id', $lead->inventory_unit_id ?? '') }}';

    async function loadUnits() {
        const projectId = projectFilter.value;
        const params = new URLSearchParams();
        if (projectId) params.set('project_id', projectId);
        const res = await fetch('{{ route('inventory.units.search') }}?' + params.toString());
        const units = await res.json();
        unitSelect.innerHTML = '<option value="">No unit assigned</option>';
        units.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.label + ' (' + u.status + ')';
            if (String(u.id) === String(selectedUnit)) opt.selected = true;
            unitSelect.appendChild(opt);
        });
    }

    projectFilter.addEventListener('change', loadUnits);
    loadUnits();
});
</script>
@endif
