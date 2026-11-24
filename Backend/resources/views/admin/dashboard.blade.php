@extends('layouts.admin')

@section('title', 'Admin Dashboard - Rumu App')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . Auth::user()->name . '!')

@section('admin-content')
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6 lg:mb-8">
        <!-- Stat Card 1 -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 hover-lift border-l-4 border-primary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Total Users</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\User::count() }}
                    </p>
                </div>
                <div class="bg-primary/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Realtors</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\User::where('role', 'realtor')->count() }}
                    </p>
                </div>
                <div class="bg-green-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Admins</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\User::where('role', 'admin')->count() }}
                    </p>
                </div>
                <div class="bg-blue-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Active Today</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">0</p>
                </div>
                <div class="bg-purple-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-purple-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 lg:gap-6 mb-4 sm:mb-6">
        <!-- Leads by Status (Pie Chart) -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Leads by Status
            </h3>
            <div class="relative h-64 sm:h-72">
                <canvas id="leadsStatusChart"></canvas>
            </div>
        </div>

        <!-- Campaigns by Status (Pie Chart) -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
                Campaigns by Status
            </h3>
            <div class="relative h-64 sm:h-72">
                <canvas id="campaignsStatusChart"></canvas>
            </div>
        </div>

        <!-- Realtors by Plan (Pie Chart) -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Realtors by Plan
            </h3>
            <div class="relative h-64 sm:h-72">
                <canvas id="realtorsPlanChart"></canvas>
            </div>
        </div>

        <!-- Monthly Activity (Bar Chart) -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Monthly Activity
            </h3>
            <div class="relative h-64 sm:h-72">
                <canvas id="monthlyActivityChart"></canvas>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart.js default config
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#6b7280';
    
    // Leads by Status Pie Chart
    const leadsCtx = document.getElementById('leadsStatusChart').getContext('2d');
    new Chart(leadsCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_keys($leadsByStatus)) !!},
            datasets: [{
                data: {!! json_encode(array_values($leadsByStatus)) !!},
                backgroundColor: [
                    '#3b82f6', // New - Blue
                    '#f59e0b', // Contacted - Yellow
                    '#10b981', // Converted - Green
                    '#ef4444', // Lost - Red
                    '#6b7280'  // Other - Gray
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });

    // Campaigns by Status Pie Chart
    const campaignsCtx = document.getElementById('campaignsStatusChart').getContext('2d');
    new Chart(campaignsCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_keys($campaignsByStatus)) !!},
            datasets: [{
                data: {!! json_encode(array_values($campaignsByStatus)) !!},
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#6b7280'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });

    // Realtors by Plan Pie Chart
    const RealtorsPlanCtx = document.getElementById('realtorsPlanChart').getContext('2d');
    new Chart(RealtorsPlanCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(collect($realtorsByPlan)->keys()->map(fn($id) => $planNames[$id] ?? 'Unknown')->values()) !!},
            datasets: [{
                data: {!! json_encode(array_values($realtorsByPlan)) !!},
                backgroundColor: [
                    '#6366f1', // Primary
                    '#ec4899', // Pink
                    '#14b8a6', // Teal
                    '#f97316', // Orange
                    '#8b5cf6'  // Violet
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
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
            datasets: [
                {
                    label: 'Leads',
                    data: {!! json_encode($leadsMonthly) !!},
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                },
                {
                    label: 'Campaigns',
                    data: {!! json_encode($campaignsMonthly) !!},
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
@endsection