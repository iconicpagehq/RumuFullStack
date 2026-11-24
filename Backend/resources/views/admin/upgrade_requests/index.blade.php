@extends('layouts.admin')

@section('title', 'Plan Upgrade Requests - Rumu App')

@section('page-title', 'Plan Upgrade Requests')
@section('page-subtitle', 'Manage Realtor plan upgrade requests')

@section('header-actions')
    @if($pendingCount > 0)
        <span class="bg-yellow-100 text-yellow-800 px-3 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap">
            {{ $pendingCount }} Pending
        </span>
    @endif
@endsection

@section('admin-content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
        {{-- Mobile: card layout --}}
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($requests as $request)
                @php
                    $expiresAt = $request->displayExpiresAt();
                    $daysLeft = $request->expiryDaysRemaining();
                    $isExpired = $request->isPlanExpired();
                @endphp
                <div class="p-4 space-y-3">
                    <div class="flex items-start gap-3">
                        @if($request->user->avatar_url)
                            <img src="{{ $request->user->avatar_url }}" alt="" class="w-11 h-11 rounded-full object-cover shrink-0">
                        @else
                            <div class="w-11 h-11 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0">
                                {{ $request->user->avatar_initials }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $request->user->name }}</p>
                            <p class="text-xs text-gray-500 break-all mt-0.5">{{ $request->user->email }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $request->created_at->format('M d, Y · h:i A') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ $request->currentPlan->name }}
                        </span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6"></path>
                        </svg>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            {{ $request->requestedPlan->name }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($request->status === 'pending')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
                        @elseif($request->status === 'approved')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Approved</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Rejected</span>
                        @endif

                        @if($expiresAt)
                            @if($isExpired)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Expired</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} left
                                </span>
                            @endif
                        @endif
                    </div>

                    @if($expiresAt)
                        <p class="text-xs {{ $isExpired ? 'text-red-600' : 'text-gray-500' }}">
                            @if($isExpired)
                                Expired on {{ $expiresAt->format('M d, Y') }}
                            @else
                                Valid until {{ $expiresAt->format('M d, Y') }}
                            @endif
                        </p>
                    @endif

                    @if($request->status === 'pending')
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button type="button" onclick='showApproveModal({{ $request->id }}, @json($request->requestedPlan->name))'
                                class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-3 px-3 rounded-lg transition touch-manipulation min-h-[44px]">
                                Approve
                            </button>
                            <button type="button" onclick="showRejectModal({{ $request->id }})"
                                class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-3 px-3 rounded-lg transition touch-manipulation min-h-[44px]">
                                Reject
                            </button>
                        </div>
                    @else
                        <div class="text-xs text-gray-600 bg-gray-50 rounded-lg p-3 space-y-1">
                            @if($request->approvedBy)
                                <p><span class="text-gray-400">Processed by</span> {{ $request->approvedBy->name }}</p>
                            @endif
                            @if($request->approved_at)
                                <p class="text-gray-500">{{ $request->approved_at->format('M d, Y h:i A') }}</p>
                            @endif
                            @if($request->admin_notes)
                                <p class="text-gray-600 italic border-l-2 border-gray-200 pl-2 mt-1">{{ $request->admin_notes }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-10 text-center">
                    <svg class="w-14 h-14 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900">No upgrade requests found</h3>
                    <p class="text-gray-500 text-sm mt-1">Realtors have not requested any plan upgrades yet.</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop: table layout --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Realtor</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Validity</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Date</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requests as $request)
                        @php
                            $expiresAt = $request->displayExpiresAt();
                            $daysLeft = $request->expiryDaysRemaining();
                            $isExpired = $request->isPlanExpired();
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 lg:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($request->user->avatar_url)
                                        <img src="{{ $request->user->avatar_url }}" alt="" class="w-9 h-9 rounded-full object-cover shrink-0">
                                    @else
                                        <div class="w-9 h-9 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0">
                                            {{ $request->user->avatar_initials }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $request->user->name }}</div>
                                        <div class="text-xs text-gray-500 truncate">{{ $request->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $request->currentPlan->name }}
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $request->requestedPlan->name }}
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                @if($request->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
                                @elseif($request->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Approved</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Rejected</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-4 hidden lg:table-cell">
                                @if($expiresAt)
                                    @if($isExpired)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Expired</span>
                                        <p class="text-xs text-gray-500 mt-1">{{ $expiresAt->format('M d, Y') }}</p>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} left
                                        </span>
                                        <p class="text-xs text-gray-500 mt-1">Until {{ $expiresAt->format('M d, Y') }}</p>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-4 text-sm text-gray-500 hidden lg:table-cell whitespace-nowrap">
                                <div>{{ $request->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $request->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-4 lg:px-6 py-4">
                                @if($request->status === 'pending')
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" onclick='showApproveModal({{ $request->id }}, @json($request->requestedPlan->name))'
                                            class="bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-semibold py-2 px-3 rounded-lg transition">
                                            Approve
                                        </button>
                                        <button type="button" onclick="showRejectModal({{ $request->id }})"
                                            class="bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm font-semibold py-2 px-3 rounded-lg transition">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <div class="text-xs sm:text-sm text-gray-600 space-y-1">
                                        @if($request->approvedBy)
                                            <p><span class="text-gray-400">By</span> {{ $request->approvedBy->name }}</p>
                                        @endif
                                        @if($request->approved_at)
                                            <p class="text-gray-500">{{ $request->approved_at->format('M d, Y h:i A') }}</p>
                                        @endif
                                        @if($request->admin_notes)
                                            <p class="text-gray-500 italic border-l-2 border-gray-200 pl-2">{{ $request->admin_notes }}</p>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 lg:px-6 py-12 text-center">
                                <h3 class="text-base font-semibold text-gray-900">No upgrade requests found</h3>
                                <p class="text-gray-500 text-sm mt-1">Realtors have not requested any plan upgrades yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full sm:max-w-md p-5 sm:p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Approve Upgrade Request</h3>
            <p class="text-gray-600 text-sm mb-4" id="approveModalText">Are you sure you want to approve this upgrade?</p>
            <form method="POST" id="approveForm" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (optional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm" placeholder="Add any notes..."></textarea>
                </div>
                <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3">
                    <button type="button" onclick="closeApproveModal()" class="w-full sm:flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm min-h-[44px]">
                        Cancel
                    </button>
                    <button type="submit" class="w-full sm:flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm min-h-[44px]">
                        Approve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full sm:max-w-md p-5 sm:p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Reject Upgrade Request</h3>
            <p class="text-gray-600 text-sm mb-4">Please provide a reason for rejecting this request.</p>
            <form method="POST" id="rejectForm" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" placeholder="Enter reason for rejection..."></textarea>
                </div>
                <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3">
                    <button type="button" onclick="closeRejectModal()" class="w-full sm:flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm min-h-[44px]">
                        Cancel
                    </button>
                    <button type="submit" class="w-full sm:flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm min-h-[44px]">
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function showApproveModal(requestId, planName) {
        document.getElementById('approveForm').action = `/admin/upgrade-requests/${requestId}/approve`;
        document.getElementById('approveModalText').textContent =
            `Approve upgrade to ${planName}? Pro plans are valid for 30 days after approval.`;
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function showRejectModal(requestId) {
        document.getElementById('rejectForm').action = `/admin/upgrade-requests/${requestId}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }

    document.getElementById('approveModal').addEventListener('click', function (e) {
        if (e.target === this) closeApproveModal();
    });
    document.getElementById('rejectModal').addEventListener('click', function (e) {
        if (e.target === this) closeRejectModal();
    });
</script>
@endsection
