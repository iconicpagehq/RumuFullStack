@extends('layouts.admin')

@section('title', 'Subscription Analytics - RUMU Admin')
@section('page-title', 'Subscription Analytics')
@section('page-subtitle', 'Track active, expired, and new subscriptions')

@section('admin-content')
<div class="space-y-6">
    <!-- Filter State & Reset -->
    <div class="flex justify-between items-center -mb-2">
        <h3 class="text-lg font-bold text-gray-900">Overview & Filters</h3>
        <button id="resetFilterBtn" onclick="filterSubscriptions('all')" class="hidden px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
            Reset to All
        </button>
    </div>

    <!-- Top KPIs (Interactive Filters) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div id="card-active" onclick="filterSubscriptions('active')" class="bg-white rounded-xl shadow-sm border-2 border-transparent p-6 cursor-pointer hover:border-primary transition duration-200 hover:shadow-md relative overflow-hidden group">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Active Subscriptions</h4>
            <div class="text-2xl font-bold text-green-600">{{ number_format($kpis['active']) }}</div>
            <div class="text-xs text-primary font-medium mt-2 opacity-0 group-hover:opacity-100 transition-opacity">Click to filter active &rarr;</div>
        </div>
        <div id="card-expired" onclick="filterSubscriptions('expired')" class="bg-white rounded-xl shadow-sm border-2 border-transparent p-6 cursor-pointer hover:border-red-500 transition duration-200 hover:shadow-md relative overflow-hidden group">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Expired Subscriptions</h4>
            <div class="text-2xl font-bold text-red-600">{{ number_format($kpis['expired']) }}</div>
            <div class="text-xs text-red-600 font-medium mt-2 opacity-0 group-hover:opacity-100 transition-opacity">Click to filter expired &rarr;</div>
        </div>
        <div id="card-new" onclick="filterSubscriptions('new')" class="bg-white rounded-xl shadow-sm border-2 border-transparent p-6 cursor-pointer hover:border-blue-500 transition duration-200 hover:shadow-md relative overflow-hidden group">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">New This Month</h4>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($kpis['new_this_month']) }}</div>
            <div class="text-xs text-blue-600 font-medium mt-2 opacity-0 group-hover:opacity-100 transition-opacity">Click to filter new &rarr;</div>
        </div>
    </div>

    <!-- All Subscriptions Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-bold text-gray-900" id="tableTitle">All Subscriptions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-white text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        <th class="px-6 py-4 font-semibold">Customer</th>
                        <th class="px-6 py-4 font-semibold">Plan Details</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Start Date</th>
                        <th class="px-6 py-4 font-semibold">Expiry Date</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white" id="subscriptionsTableBody">
                    @forelse($subscriptions as $sub)
                        @php
                            $isNewThisMonth = $sub->created_at >= now()->startOfMonth() ? 'true' : 'false';
                            $statusClass = match(strtolower($sub->status)) {
                                'active' => 'bg-green-100 text-green-800',
                                'expired' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                                default => 'bg-yellow-100 text-yellow-800',
                            };
                        @endphp
                        <tr class="subscription-row hover:bg-gray-50 transition" data-status="{{ strtolower($sub->status) }}" data-new="{{ $isNewThisMonth }}">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $sub->user->name ?? 'Unknown Customer' }}</div>
                                <div class="text-xs text-gray-500">{{ $sub->user->email ?? 'No email provided' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span class="font-semibold text-gray-900">{{ $sub->plan_name }}</span>
                                <div class="text-xs text-gray-500">{{ $sub->duration_months }} Months Plan</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($sub->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $sub->start_date ? $sub->start_date->format('M d, Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $sub->expiry_date ? $sub->expiry_date->format('M d, Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    <button type="button" 
                                        onclick="openEditModal({{ $sub->id }}, '{{ $sub->status }}', '{{ $sub->expiry_date ? $sub->expiry_date->format('Y-m-d') : '' }}', '{{ addslashes($sub->internal_notes ?? '') }}')"
                                        class="text-primary hover:text-primary/80 font-medium transition">
                                        Edit
                                    </button>
                                    
                                    @if($sub->user)
                                        @if($sub->user->isSubscriptionSuspended())
                                            <form action="{{ route('admin.realtors.resume-subscription', $sub->user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 font-medium transition" onclick="return confirm('Are you sure you want to resume this subscription?')">
                                                    Resume
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.realtors.suspend-subscription', $sub->user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-800 font-medium transition" onclick="return confirm('Are you sure you want to suspend this subscription?')">
                                                    Suspend
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400 mb-2">
                                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900">No subscriptions found</h3>
                                <p class="mt-1 text-sm text-gray-500">There are no subscription records available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Empty State for Filter -->
        <div id="emptyFilterState" class="hidden px-6 py-12 text-center">
            <div class="text-gray-300 mb-2">
                <svg class="mx-auto h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </div>
            <h3 class="text-sm font-medium text-gray-900">No matches found</h3>
            <p class="mt-1 text-sm text-gray-500">No subscriptions match the selected filter.</p>
        </div>
    </div>
</div>

<!-- Edit Subscription Modal -->
<div id="editSubscriptionModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" onclick="closeEditModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-primary/10 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                        Edit Subscription
                    </h3>
                    <div class="mt-4">
                        <form id="editSubscriptionForm" method="POST" action="">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md shadow-sm border">
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                <input type="date" id="expiry_date" name="expiry_date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            </div>

                            <div class="mb-4">
                                <label for="internal_notes" class="block text-sm font-medium text-gray-700">Internal Notes</label>
                                <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                            </div>

                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm transition">
                                    Save Changes
                                </button>
                                <button type="button" onclick="closeEditModal()" class="mt-3 inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentFilter = 'all';

    function filterSubscriptions(filterType) {
        if (currentFilter === filterType) {
            filterType = 'all'; // Toggle off if clicked again
        }
        
        currentFilter = filterType;
        
        // Reset all card styles
        const activeCard = document.getElementById('card-active');
        const expiredCard = document.getElementById('card-expired');
        const newCard = document.getElementById('card-new');

        [activeCard, expiredCard, newCard].forEach(card => {
            card.classList.remove('border-primary', 'bg-primary/5', 'border-red-500', 'bg-red-50', 'border-blue-500', 'bg-blue-50');
            card.classList.add('border-transparent');
        });

        // Update Reset Button Visibility & Title
        const resetBtn = document.getElementById('resetFilterBtn');
        const tableTitle = document.getElementById('tableTitle');
        
        let visibleCount = 0;

        if (filterType === 'all') {
            resetBtn.classList.add('hidden');
            tableTitle.textContent = 'All Subscriptions';
        } else {
            resetBtn.classList.remove('hidden');
            if (filterType === 'active') {
                activeCard.classList.remove('border-transparent');
                activeCard.classList.add('border-primary', 'bg-primary/5');
                tableTitle.textContent = 'Active Subscriptions';
            } else if (filterType === 'expired') {
                expiredCard.classList.remove('border-transparent');
                expiredCard.classList.add('border-red-500', 'bg-red-50');
                tableTitle.textContent = 'Expired Subscriptions';
            } else if (filterType === 'new') {
                newCard.classList.remove('border-transparent');
                newCard.classList.add('border-blue-500', 'bg-blue-50');
                tableTitle.textContent = 'New Subscriptions (This Month)';
            }
        }

        // Filter Rows
        const rows = document.querySelectorAll('.subscription-row');
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const isNew = row.getAttribute('data-new') === 'true';
            let show = false;

            if (filterType === 'all') {
                show = true;
            } else if (filterType === 'active' && status === 'active') {
                show = true;
            } else if (filterType === 'expired' && status === 'expired') {
                show = true;
            } else if (filterType === 'new' && isNew) {
                show = true;
            }

            if (show) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        // Handle Empty State
        const emptyState = document.getElementById('emptyFilterState');
        if (visibleCount === 0 && rows.length > 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }

    function openEditModal(id, status, expiryDate, internalNotes) {
        // Set form action
        const form = document.getElementById('editSubscriptionForm');
        form.action = `/admin/billing/subscriptions/${id}`;
        
        // Populate fields
        document.getElementById('status').value = status;
        document.getElementById('expiry_date').value = expiryDate;
        document.getElementById('internal_notes').value = internalNotes;
        
        // Show modal
        document.getElementById('editSubscriptionModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editSubscriptionModal').classList.add('hidden');
    }
</script>
@endsection
