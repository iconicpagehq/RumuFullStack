@extends('layouts.admin')

@section('title', 'Plans Management - Rumu App')

@section('page-title', 'Plans Management')
@section('page-subtitle', 'Manage subscription plans and pricing')

@section('header-actions')
    <a href="{{ route('admin.plans.create') }}"
        class="bg-secondary hover:bg-secondary/90 text-primary dark:text-gray-900 px-3 sm:px-4 py-2.5 sm:py-2 rounded-lg transition flex items-center justify-center space-x-2 text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 whitespace-nowrap">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>Create Plan</span>
    </a>
@endsection

@section('admin-content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="bg-amber-100 border border-amber-400 text-amber-800 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6">
        @foreach($plans as $plan)
            <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 border-l-4 
                                {{ !$plan->is_active ? 'border-gray-300 opacity-75' : ($plan->name === 'Pro' ? 'border-purple-500' : 'border-blue-500') }}">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800">{{ $plan->name }} Plan</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                            {{ $plan->users_count }} {{ Str::plural('user', $plan->users_count) }}
                        </p>
                    </div>
                    <div class="bg-{{ $plan->name === 'Pro' ? 'purple' : 'blue' }}-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-{{ $plan->name === 'Pro' ? 'purple' : 'blue' }}-500"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                            </path>
                        </svg>
                    </div>
                </div>

                <div class="space-y-2 sm:space-y-3">
                    <div class="flex justify-between items-center py-1.5 sm:py-2 border-b border-gray-100">
                        <span class="text-xs sm:text-sm text-gray-600">Max Campaigns</span>
                        <span class="text-xs sm:text-sm font-semibold text-gray-800">
                            {{ $plan->max_campaigns ?? 'Unlimited' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 sm:py-2 border-b border-gray-100">
                        <span class="text-xs sm:text-sm text-gray-600">Leads per Campaign</span>
                        <span class="text-xs sm:text-sm font-semibold text-gray-800">
                            {{ $plan->max_leads_per_campaign ?? 'Unlimited' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 sm:py-2">
                        <span class="text-xs sm:text-sm text-gray-600">Price</span>
                        <span class="text-base sm:text-lg font-bold text-{{ $plan->name === 'Pro' ? 'purple' : 'blue' }}-600">
                            ₹{{ number_format($plan->price ?? 0) }}/mo
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Plans Table -->
    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
        <div
            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 border-b border-gray-200 bg-gradient-to-r from-primary to-primary/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="text-base sm:text-lg lg:text-xl font-bold text-white">All Plans</h2>
            <p class="text-white/80 text-xs sm:text-sm">Drag rows using the handle to rearrange display order</p>
        </div>
        <div class="overflow-x-auto -mx-3 sm:mx-0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-3 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                            Order
                        </th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Plan Name</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                            Price</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Users</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Campaigns</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                            Leads/Campaign</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="plans-sortable" class="bg-white divide-y divide-gray-200">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-gray-50 transition {{ !$plan->is_active ? 'bg-gray-50/80' : '' }}" data-plan-id="{{ $plan->id }}" draggable="true">
                            <td class="px-2 sm:px-3 py-2.5 sm:py-3 lg:py-4">
                                <button type="button"
                                    class="plan-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 p-1 rounded touch-manipulation"
                                    title="Drag to reorder"
                                    aria-label="Drag to reorder {{ $plan->name }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM7 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM7 16a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM13 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM13 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM13 16a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                                <div class="flex items-center">
                                    <div
                                        class="w-8 h-8 sm:w-10 sm:h-10 bg-{{ $plan->name === 'Pro' ? 'purple' : 'blue' }}-500/10 rounded-full flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-{{ $plan->name === 'Pro' ? 'purple' : 'blue' }}-500"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-gray-900">{{ $plan->name }}</div>
                                        <div class="text-xs text-gray-500 sm:hidden mt-0.5">
                                            ₹{{ number_format($plan->price ?? 0) }}/mo</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 hidden sm:table-cell">
                                <div class="text-sm font-semibold text-gray-900">₹{{ number_format($plan->price ?? 0) }}</div>
                                <div class="text-xs text-gray-500">per month</div>
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                                <span
                                    class="px-2 sm:px-2.5 lg:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $plan->users_count }} {{ Str::plural('user', $plan->users_count) }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden md:table-cell">
                                {{ $plan->max_campaigns ?? 'Unlimited' }}
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden lg:table-cell">
                                {{ $plan->max_leads_per_campaign ?? 'Unlimited' }}
                            </td>
                            <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                                <div class="flex flex-col sm:flex-row gap-1.5 sm:gap-2">
                                    <a href="{{ route('admin.plans.edit', $plan) }}"
                                        class="text-primary hover:text-primary/80 text-sm font-medium py-1.5 px-2 touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center sm:justify-start bg-primary/5 sm:bg-transparent rounded">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.plans.toggle-status', $plan) }}" class="inline"
                                        onsubmit="return confirm('{{ $plan->is_active ? 'Deactivate' : 'Activate' }} plan {{ $plan->name }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="text-sm font-medium py-1.5 px-2 touch-manipulation min-h-[44px] sm:min-h-0 w-full sm:w-auto rounded {{ $plan->is_active ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-green-700 bg-green-50 hover:bg-green-100' }}">
                                            {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 sm:px-4 lg:px-6 py-8 text-center text-gray-500 text-sm">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                                    </path>
                                </svg>
                                <p class="mt-2">No plans found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="plan-reorder-status" class="hidden px-4 py-2 text-sm border-t border-gray-100"></div>
    </div>

    <!-- Plan Features Comparison -->
    <div class="mt-4 sm:mt-6 lg:mt-8 bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
        <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800 mb-3 sm:mb-4">Plan Features</h2>
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-left text-xs sm:text-sm font-semibold text-gray-700">
                            Feature</th>
                        @foreach($plans as $plan)
                            <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700">
                                {{ $plan->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-600">Max Campaigns</td>
                        @foreach($plans as $plan)
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-center text-xs sm:text-sm font-medium text-gray-900">
                                {{ $plan->max_campaigns ?? '∞' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-600">Leads per Campaign</td>
                        @foreach($plans as $plan)
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-center text-xs sm:text-sm font-medium text-gray-900">
                                {{ $plan->max_leads_per_campaign ?? '∞' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-600">Status</td>
                        @foreach($plans as $plan)
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-center text-xs sm:text-sm font-medium {{ $plan->is_active ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-gray-600">Active Users</td>
                        @foreach($plans as $plan)
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-center text-xs sm:text-sm font-medium text-green-600">
                                {{ $plan->users_count }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const tbody = document.getElementById('plans-sortable');
    const statusEl = document.getElementById('plan-reorder-status');
    if (!tbody) return;

    let draggedRow = null;

    function showStatus(message, type) {
        if (!statusEl) return;
        statusEl.textContent = message;
        statusEl.className = 'px-4 py-2 text-sm border-t border-gray-100 ' + (
            type === 'error' ? 'text-red-600 bg-red-50' :
            type === 'success' ? 'text-green-700 bg-green-50' :
            'text-gray-600 bg-gray-50'
        );
        statusEl.classList.remove('hidden');
    }

    function currentOrder() {
        return Array.from(tbody.querySelectorAll('tr[data-plan-id]')).map(function (row) {
            return parseInt(row.dataset.planId, 10);
        });
    }

    function saveOrder() {
        const order = currentOrder();
        showStatus('Saving order...', 'info');

        fetch(@json(route('admin.plans.reorder')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
            },
            body: JSON.stringify({ order: order }),
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Failed to save order');
            return response.json();
        })
        .then(function () {
            showStatus('Plan order saved.', 'success');
            window.setTimeout(function () { window.location.reload(); }, 600);
        })
        .catch(function () {
            showStatus('Could not save order. Please refresh and try again.', 'error');
        });
    }

    tbody.addEventListener('dragstart', function (event) {
        const row = event.target.closest('tr[data-plan-id]');
        if (!row) return;

        draggedRow = row;
        row.classList.add('opacity-50', 'bg-primary/5');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.planId);
    });

    tbody.addEventListener('dragend', function () {
        if (draggedRow) {
            draggedRow.classList.remove('opacity-50', 'bg-primary/5');
            draggedRow = null;
        }
    });

    tbody.addEventListener('dragover', function (event) {
        event.preventDefault();
        const row = event.target.closest('tr[data-plan-id]');
        if (!row || row === draggedRow) return;

        const rect = row.getBoundingClientRect();
        const after = event.clientY > rect.top + rect.height / 2;
        tbody.insertBefore(draggedRow, after ? row.nextSibling : row);
    });

    tbody.addEventListener('drop', function (event) {
        event.preventDefault();
        if (draggedRow) {
            saveOrder();
        }
    });

    tbody.querySelectorAll('.plan-drag-handle').forEach(function (handle) {
        handle.addEventListener('mousedown', function (event) {
            event.stopPropagation();
        });
    });
})();
</script>
@endsection