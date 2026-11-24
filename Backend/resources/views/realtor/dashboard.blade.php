@extends('layouts.app')

@section('title', 'Realtor Dashboard - Rumu App')

@section('header-title', 'Dashboard')
@section('header-subtitle', 'Welcome to your workspace!')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('realtor.partials.sidebar')

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <!-- Content Area -->
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="animate-fade-in">
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('error') }}</div>
                    @endif

                    @if(($planExpiryWarning ?? null) && !auth()->user()->isSubscriptionSuspended())
                        <div class="bg-amber-50 border border-amber-300 rounded-xl shadow-md p-4 sm:p-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-amber-100 p-2 rounded-lg shrink-0">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-amber-900">Your {{ $planExpiryWarning['plan_name'] }} plan is expiring soon</h3>
                                    <p class="text-amber-800 text-sm mt-1">
                                        @if($planExpiryWarning['days_remaining'] > 0)
                                            Expires in <strong>{{ $planExpiryWarning['days_remaining'] }} {{ Str::plural('day', $planExpiryWarning['days_remaining']) }}</strong>
                                            ({{ $planExpiryWarning['expires_formatted'] }}).
                                        @else
                                            Expires <strong>today</strong> ({{ $planExpiryWarning['expires_formatted'] }}).
                                        @endif
                                        Renew now to keep your plan benefits.
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('realtor.plans') }}"
                                class="inline-flex items-center justify-center bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2.5 px-5 rounded-lg transition text-sm sm:text-base whitespace-nowrap shrink-0 min-h-[44px] sm:min-h-0">
                                Renew Plan
                            </a>
                        </div>
                    @endif

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
                        <!-- Stat Card 1 -->
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-primary">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-xs sm:text-sm font-medium">Active Projects</p>
                                    <p class="text-2xl sm:text-3xl font-bold text-gray-800 mt-1 sm:mt-2">{{ $campaignStatusData['active'] }}</p>
                                </div>
                                <div class="bg-primary/10 p-2 sm:p-3 rounded-lg">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Stat Card 2 -->
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-green-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-xs sm:text-sm font-medium">Completed</p>
                                    <p class="text-2xl sm:text-3xl font-bold text-gray-800 mt-1 sm:mt-2">{{ $campaignStatusData['completed'] }}</p>
                                </div>
                                <div class="bg-green-500/10 p-2 sm:p-3 rounded-lg">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-green-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Stat Card 3 -->
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-yellow-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-xs sm:text-sm font-medium">Total Leads</p>
                                    <p class="text-2xl sm:text-3xl font-bold text-gray-800 mt-1 sm:mt-2">{{ array_sum($leadStatusData) }}</p>
                                </div>
                                <div class="bg-yellow-500/10 p-2 sm:p-3 rounded-lg">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Stat Card 4 -->
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-blue-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-xs sm:text-sm font-medium">Won Leads</p>
                                    <p class="text-2xl sm:text-3xl font-bold text-gray-800 mt-1 sm:mt-2">{{ $leadStatusData['won'] }}</p>
                                </div>
                                <div class="bg-blue-500/10 p-2 sm:p-3 rounded-lg">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-blue-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 sm:mb-8">
                        <!-- Campaign Status Pie Chart -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Campaign Status Distribution</h3>
                            <div class="relative h-64">
                                <canvas id="campaignStatusChart"></canvas>
                            </div>
                        </div>

                        <!-- Lead Status Pie Chart -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Leads by Status</h3>
                            <div class="relative h-64">
                                <canvas id="leadStatusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Activity Bar Chart -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Monthly Activity (Last 6 Months)</h3>
                        <div class="relative h-72">
                            <canvas id="monthlyActivityChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Campaigns -->
                    @if($recentCampaigns->count() > 0)
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Campaigns</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leads</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($recentCampaigns as $campaign)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $campaign->name }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800' : 
                                                   ($campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($campaign->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $campaign->leads->count() }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $campaign->created_at->format('M d, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Campaign Status Pie Chart
        const campaignCtx = document.getElementById('campaignStatusChart').getContext('2d');
        new Chart(campaignCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Paused', 'Completed'],
                datasets: [{
                    data: [{{ $campaignStatusData['active'] }}, {{ $campaignStatusData['paused'] }}, {{ $campaignStatusData['completed'] }}],
                    backgroundColor: ['#22c55e', '#eab308', '#6b7280'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Lead Status Pie Chart
        const leadCtx = document.getElementById('leadStatusChart').getContext('2d');
        new Chart(leadCtx, {
            type: 'pie',
            data: {
                labels: ['New', 'Contacted', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'],
                datasets: [{
                    data: [
                        {{ $leadStatusData['new'] }},
                        {{ $leadStatusData['contacted'] }},
                        {{ $leadStatusData['qualified'] }},
                        {{ $leadStatusData['proposal'] }},
                        {{ $leadStatusData['negotiation'] }},
                        {{ $leadStatusData['won'] }},
                        {{ $leadStatusData['lost'] }}
                    ],
                    backgroundColor: [
                        '#3b82f6', // new - blue
                        '#8b5cf6', // contacted - purple
                        '#ec4899', // qualified - pink
                        '#f97316', // proposal - orange
                        '#eab308', // negotiation - yellow
                        '#22c55e', // won - green
                        '#ef4444'  // lost - red
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Monthly Activity Bar Chart
        const monthlyCtx = document.getElementById('monthlyActivityChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($months) !!},
                datasets: [{
                    label: 'New Leads',
                    data: {!! json_encode($monthlyLeads) !!},
                    backgroundColor: '#6f0159',
                    borderRadius: 4
                }, {
                    label: 'New Campaigns',
                    data: {!! json_encode($monthlyCampaigns) !!},
                    backgroundColor: '#22c55e',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>
@endsection
