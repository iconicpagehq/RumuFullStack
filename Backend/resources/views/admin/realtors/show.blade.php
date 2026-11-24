@extends('layouts.admin')

@section('title', $realtor->name . ' - Rumu App Admin')

@section('page-title', $realtor->name)
@section('page-subtitle', 'Detailed Realtor profile')

@section('header-actions')
    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
        <a href="{{ route('admin.realtors.index') }}"
            class="text-gray-600 hover:text-gray-900 font-medium text-sm sm:text-base py-2 sm:py-0 px-3 sm:px-0 text-center sm:text-left touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center sm:justify-start bg-gray-50 sm:bg-transparent rounded-lg sm:rounded-none">
            Back to List</a>
        @if($realtor->isSubscriptionSuspended())
            <form method="POST" action="{{ route('admin.realtors.resume-subscription', $realtor) }}" class="inline"
                onsubmit="return confirm('Resume subscription for {{ $realtor->name }}?');">
                @csrf
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 sm:py-2 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center whitespace-nowrap w-full sm:w-auto">
                    Resume Subscription
                </button>
            </form>
        @else
            <button type="button" onclick="document.getElementById('suspendModal').classList.remove('hidden')"
                class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2.5 sm:py-2 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center whitespace-nowrap w-full sm:w-auto">
                Suspend Subscription
            </button>
        @endif
        <a href="{{ route('admin.realtors.edit', $realtor) }}"
            class="bg-primary hover:bg-primary/90 text-white font-semibold py-2.5 sm:py-2 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center whitespace-nowrap">
            Edit Profile</a>
        <a href="{{ route('admin.realtors.billing', $realtor) }}"
            class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2.5 sm:py-2 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center whitespace-nowrap">
            Manage Billing</a>
    </div>
@endsection

@section('admin-content')
    <div class="max-w-7xl mx-auto">
        @if(session('success'))
            <div
                class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-4 sm:mb-6 animate-fade-in text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-4 sm:mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($realtor->isSubscriptionSuspended())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 sm:p-5 mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-red-800">Subscription Suspended</h3>
                        <p class="text-sm text-red-700 mt-1">
                            Since {{ $realtor->subscription_suspended_at->format('M d, Y h:i A') }}
                            @if($realtor->suspendedPlan)
                                · Previous plan: <strong>{{ $realtor->suspendedPlan->name }}</strong>
                            @endif
                        </p>
                        @if($realtor->subscription_suspend_reason)
                            <p class="text-sm text-red-600 mt-2 italic">"{{ $realtor->subscription_suspend_reason }}"</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 shrink-0">
                        Suspended
                    </span>
                </div>
            </div>
        @endif

        <!-- Profile Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6 lg:mb-8">
            <!-- Realtor Info -->
            <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
                <div class="flex items-center space-x-3 sm:space-x-4 mb-4 sm:mb-6">
                    <div
                        class="h-12 w-12 sm:h-14 sm:w-14 lg:h-16 lg:w-16 rounded-full bg-secondary text-primary flex items-center justify-center font-bold text-lg sm:text-xl lg:text-2xl flex-shrink-0">
                        {{ strtoupper(substr($realtor->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 truncate">{{ $realtor->name }}</h2>
                        <p class="text-gray-500 text-xs sm:text-sm truncate">{{ $realtor->email }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-3 sm:pt-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 text-sm">Plan</span>
                        <span
                            class="px-2 py-1 text-xs font-semibold rounded-full {{ $realtor->plan->name == 'Pro' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $realtor->plan->name }}
                        </span>
                    </div>
                    @if($realtor->isSubscriptionSuspended() && $realtor->suspendedPlan)
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600 text-sm">Restores to</span>
                            <span class="text-gray-800 text-sm font-medium">{{ $realtor->suspendedPlan->name }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 text-sm">Joined</span>
                        <span
                            class="text-gray-800 text-sm font-medium">{{ $realtor->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 text-sm">Total Leads</span>
                        <span class="text-gray-800 text-sm font-medium">
                            {{ $realtor->campaigns->sum(function ($campaign) {
        return $campaign->leads->count(); }) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Campaigns Stats -->
            <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6 md:col-span-2">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4">Performance Overview</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 lg:gap-4">
                    <div class="bg-blue-50 rounded-lg p-2.5 sm:p-3 lg:p-4 text-center">
                        <p class="text-blue-600 text-xs font-semibold uppercase">Campaigns</p>
                        <p class="text-xl sm:text-2xl font-bold text-blue-900 mt-0.5 sm:mt-1">{{ $realtor->campaigns->count() }}
                        </p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-2.5 sm:p-3 lg:p-4 text-center">
                        <p class="text-green-600 text-xs font-semibold uppercase">Total Leads</p>
                        <p class="text-xl sm:text-2xl font-bold text-green-900 mt-0.5 sm:mt-1">
                            {{ $realtor->campaigns->sum(function ($campaign) {
        return $campaign->leads->count(); }) }}
                        </p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-2.5 sm:p-3 lg:p-4 text-center">
                        <p class="text-yellow-600 text-xs font-semibold uppercase">Active</p>
                        <p class="text-xl sm:text-2xl font-bold text-yellow-900 mt-0.5 sm:mt-1">
                            {{ $realtor->campaigns->where('status', 'active')->count() }}
                        </p>
                    </div>
                    <div class="bg-indigo-50 rounded-lg p-2.5 sm:p-3 lg:p-4 text-center">
                        <p class="text-indigo-600 text-xs font-semibold uppercase">Won</p>
                        <p class="text-xl sm:text-2xl font-bold text-indigo-900 mt-0.5 sm:mt-1">
                            {{ $realtor->campaigns->sum(function ($campaign) {
        return $campaign->leads->where('status', 'won')->count(); }) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Campaigns -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
            <div class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-bold text-gray-800">Realtor's Campaigns</h3>
            </div>
            @if($realtor->campaigns->count() > 0)
                <div class="overflow-x-auto -mx-3 sm:mx-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign Name</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Status</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Leads</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Created</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($realtor->campaigns as $campaign)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $campaign->name }}</div>
                                    </td>
                                    <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 hidden sm:table-cell">
                                        <span class="px-2 sm:px-2.5 lg:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800' : ($campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden md:table-cell">
                                        {{ $campaign->leads->count() }}
                                    </td>
                                    <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden lg:table-cell">
                                        {{ $campaign->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                                        <a href="{{ route('campaigns.show', $campaign) }}"
                                            class="text-primary hover:text-primary/70 text-sm font-medium">View Campaign</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-gray-500 italic">
                    No campaigns created yet.
                </div>
            @endif
        </div>
    </div>

    <!-- Suspend Modal -->
    <div id="suspendModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Suspend Subscription</h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ $realtor->name }} will be moved to Basic and cannot create campaigns or leads until resumed.
            </p>
            <form method="POST" action="{{ route('admin.realtors.suspend-subscription', $realtor) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason (optional)</label>
                    <textarea name="reason" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="e.g. Payment pending"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('suspendModal').classList.add('hidden')"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 px-4 rounded-lg text-sm">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm">
                        Suspend
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection