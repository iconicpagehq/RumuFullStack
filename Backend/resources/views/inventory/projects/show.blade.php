@extends('layouts.app')

@section('title', $project->name . ' - Inventory')

@section('content')
<div class="min-h-screen bg-gray-50">
    @if(Auth::user()->role === 'admin')
        @include('admin.partials.sidebar')
    @else
        @include('realtor.partials.sidebar')
    @endif

    <main class="lg:ml-64 min-h-screen">
        @include('inventory.partials.page-header', [
            'title' => $project->name,
            'backUrl' => route('inventory.projects.index'),
            'backLabel' => '← Inventory',
            'actionUrl' => Auth::user()->role === 'realtor' ? route('inventory.projects.edit', $project) : null,
            'actionLabel' => 'Edit Project',
        ])

        <div class="p-4 sm:p-6 lg:p-8 space-y-6">
            @include('realtor.partials.subscription-suspended-banner')
            @foreach(['success' => 'green', 'error' => 'red'] as $key => $color)
                @if(session($key))
                    <div class="p-4 bg-{{ $color }}-50 border border-{{ $color }}-200 text-{{ $color }}-800 rounded-lg text-sm">{{ session($key) }}</div>
                @endif
            @endforeach

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach(['available' => 'green', 'hold' => 'yellow', 'booked' => 'blue', 'sold' => 'red'] as $key => $color)
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-{{ $color }}-500">
                    <p class="text-2xl font-bold text-{{ $color }}-700">{{ $summary[$key] }}</p>
                    <p class="text-xs text-gray-500 uppercase">{{ $key }}</p>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-1 space-y-4">
                    <div class="bg-white rounded-xl shadow-lg p-4">
                        @php
                            $floorOptions = $project->towers->flatMap(function ($tower) {
                                return $tower->floors->sortBy('floor_number')->map(fn ($floor) => [
                                    'id' => $floor->id,
                                    'label' => $tower->name . ' · ' . $floor->displayLabel() . ' (' . $floor->units->count() . ')',
                                ]);
                            });
                        @endphp
                        <label for="floor-select" class="block text-sm font-medium text-gray-700 mb-2">Floor</label>
                        @if($floorOptions->isNotEmpty())
                            <select id="floor-select"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary"
                                onchange="if (this.value) window.location.href = '{{ route('inventory.projects.show', $project) }}?floor=' + this.value">
                                <option value="">Choose a floor…</option>
                                @foreach($floorOptions as $option)
                                    <option value="{{ $option['id'] }}" {{ $selectedFloorId == $option['id'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <p class="text-sm text-gray-500">No floors yet. Add one below.</p>
                        @endif
                    </div>

                    @if(Auth::user()->role === 'realtor')
                    <div class="bg-white rounded-xl shadow-lg p-4">
                        <h3 class="font-bold text-gray-800 mb-3">Quick Add</h3>
                        <div class="flex gap-1 mb-4 bg-gray-100 p-1 rounded-lg">
                            <button type="button" onclick="showAddTab('tower')" id="tab-tower" class="add-tab flex-1 text-xs sm:text-sm py-2 rounded-md bg-white text-primary font-semibold shadow-sm">Tower</button>
                            <button type="button" onclick="showAddTab('wing')" id="tab-wing" class="add-tab flex-1 text-xs sm:text-sm py-2 rounded-md text-gray-600">Wing</button>
                            <button type="button" onclick="showAddTab('floor')" id="tab-floor" class="add-tab flex-1 text-xs sm:text-sm py-2 rounded-md text-gray-600">Floor</button>
                        </div>

                        <form method="POST" action="{{ route('inventory.towers.store', $project) }}" id="add-tower" class="add-panel space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tower name</label>
                                <input type="text" name="name" placeholder="e.g. Tower A" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                            </div>
                            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg">Add Tower</button>
                        </form>

                        @if($project->towers->isNotEmpty())
                        <form method="POST" action="{{ route('inventory.wings.store', $project->towers->first()) }}" id="add-wing" class="add-panel hidden space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tower</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" onchange="this.form.action='{{ url('inventory/towers') }}/'+this.value+'/wings'">
                                    @foreach($project->towers as $tower)
                                        <option value="{{ $tower->id }}">{{ $tower->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Wing name</label>
                                <input type="text" name="name" placeholder="e.g. Wing A" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                            </div>
                            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg">Add Wing</button>
                        </form>

                        <form method="POST" action="{{ route('inventory.floors.store', $project->towers->first()) }}" id="add-floor" class="add-panel hidden space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tower</label>
                                <select id="floor-tower" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" onchange="updateFloorFormAction()">
                                    @foreach($project->towers as $tower)
                                        <option value="{{ $tower->id }}">{{ $tower->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Wing <span class="text-gray-400">(optional)</span></label>
                                <select name="inventory_wing_id" id="floor-wing" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">No wing</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Floor number</label>
                                <input type="number" name="floor_number" placeholder="e.g. 1, 2, 3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                            </div>
                            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg">Add Floor</button>
                        </form>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <div class="bg-white rounded-xl shadow-lg p-4">
                        <form method="GET" class="flex flex-wrap gap-2 items-end">
                            <input type="hidden" name="floor" value="{{ $selectedFloorId }}">
                            <div>
                                <label class="text-xs text-gray-500">Status</label>
                                <select name="status" class="px-3 py-2 border rounded-lg text-sm">
                                    <option value="">All</option>
                                    @foreach(\App\Models\InventoryUnit::STATUSES as $s)
                                        <option value="{{ $s }}" {{ $statusFilter === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Layout</label>
                                <select name="layout" class="px-3 py-2 border rounded-lg text-sm">
                                    <option value="">All</option>
                                    @foreach(\App\Models\InventoryUnit::LAYOUTS as $l)
                                        <option value="{{ $l }}" {{ $layoutFilter === $l ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $l)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm">Filter</button>
                        </form>
                    </div>

                    @if(Auth::user()->role === 'realtor' && $selectedFloorId)
                    @php $selectedFloor = \App\Models\InventoryFloor::with('tower')->find($selectedFloorId); @endphp
                    @if($selectedFloor)
                    <div class="bg-white rounded-xl shadow-lg p-4">
                        <h3 class="font-bold mb-3">Add Units to {{ $selectedFloor->displayLabel() }}</h3>
                        <form method="POST" action="{{ route('inventory.units.bulk-store', $selectedFloor) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @csrf
                            <input type="text" name="prefix" placeholder="Prefix" class="px-3 py-2 border rounded-lg text-sm">
                            <input type="number" name="start_number" placeholder="From" required class="px-3 py-2 border rounded-lg text-sm">
                            <input type="number" name="end_number" placeholder="To" required class="px-3 py-2 border rounded-lg text-sm">
                            <select name="layout" class="px-3 py-2 border rounded-lg text-sm">
                                <option value="">Layout</option>
                                @foreach(\App\Models\InventoryUnit::LAYOUTS as $l)
                                    <option value="{{ $l }}">{{ strtoupper(str_replace('_', ' ', $l)) }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" name="base_price" placeholder="Price" class="px-3 py-2 border rounded-lg text-sm col-span-2">
                            <button class="col-span-2 bg-primary text-white py-2 rounded-lg text-sm">Bulk Add Units</button>
                        </form>
                    </div>
                    @endif
                    @endif

                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Unit</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 hidden sm:table-cell">Location</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Layout</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 hidden md:table-cell">Price</th>
                                    @if(Auth::user()->role === 'realtor')<th class="px-4 py-3"></th>@endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($units as $unit)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $unit->unit_number }}</td>
                                    <td class="px-4 py-3 text-gray-500 hidden sm:table-cell text-xs">{{ $unit->floor?->tower?->name }} · {{ $unit->floor?->displayLabel() }}</td>
                                    <td class="px-4 py-3">{{ $unit->layout ? strtoupper(str_replace('_', ' ', $unit->layout)) : '—' }}</td>
                                    <td class="px-4 py-3">@include('inventory.partials.availability-badge', ['status' => $unit->availability_status])</td>
                                    <td class="px-4 py-3 hidden md:table-cell">{{ $unit->base_price ? '₹' . number_format($unit->base_price) : '—' }}</td>
                                    @if(Auth::user()->role === 'realtor')
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('inventory.units.update-status', $unit) }}" class="inline-flex">
                                                @csrf @method('PATCH')
                                                <select name="availability_status" class="text-xs border rounded px-1 py-1" onchange="this.form.submit()">
                                                    @foreach(\App\Models\InventoryUnit::STATUSES as $s)
                                                        <option value="{{ $s }}" {{ $unit->availability_status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                            <form method="POST" action="{{ route('inventory.units.destroy', $unit) }}"
                                                onsubmit="return confirm('Delete unit {{ $unit->unit_number }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="Delete unit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No units found. Select a floor or add units.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

@if(Auth::user()->role === 'realtor')
<script>
const towerWings = @json($project->towers->mapWithKeys(fn($t) => [$t->id => $t->wings->map(fn($w) => ['id' => $w->id, 'name' => $w->name])]));

function showAddTab(tab) {
    document.querySelectorAll('.add-panel').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.add-tab').forEach(el => {
        el.classList.remove('bg-white', 'text-primary', 'font-semibold', 'shadow-sm');
        el.classList.add('text-gray-600');
    });
    const panel = document.getElementById('add-' + tab);
    const btn = document.getElementById('tab-' + tab);
    if (panel) panel.classList.remove('hidden');
    if (btn) {
        btn.classList.add('bg-white', 'text-primary', 'font-semibold', 'shadow-sm');
        btn.classList.remove('text-gray-600');
    }
}

function updateFloorFormAction() {
    const towerId = document.getElementById('floor-tower')?.value;
    const form = document.getElementById('add-floor');
    if (!towerId || !form) return;
    form.action = '/inventory/towers/' + towerId + '/floors';
    const wingSelect = document.getElementById('floor-wing');
    if (!wingSelect) return;
    wingSelect.innerHTML = '<option value="">No wing</option>';
    (towerWings[towerId] || []).forEach(w => {
        wingSelect.innerHTML += `<option value="${w.id}">${w.name}</option>`;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateFloorFormAction();
    @if($project->towers->isEmpty())
    document.getElementById('tab-wing')?.setAttribute('disabled', 'disabled');
    document.getElementById('tab-floor')?.setAttribute('disabled', 'disabled');
    @endif
});
</script>
@endif
@endsection
