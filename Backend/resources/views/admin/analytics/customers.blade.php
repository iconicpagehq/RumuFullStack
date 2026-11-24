@extends('layouts.admin')

@section('title', 'Customer Analytics - RUMU Admin')
@section('page-title', 'Customer Analytics')
@section('page-subtitle', 'Analyze customer growth and plan distribution')

@section('admin-content')
<div class="space-y-6">
    <!-- Top KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Customers</h4>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($kpis['total']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Active Customers</h4>
            <div class="text-2xl font-bold text-primary">{{ number_format($kpis['active']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">New Registrations (This Month)</h4>
            <div class="text-2xl font-bold text-green-600">{{ number_format($kpis['new_this_month']) }}</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-bold text-gray-900 mb-4">Customer Distribution by Plan</h3>
            <div class="w-full flex justify-center">
                <div style="width: 300px; height: 300px;">
                    <canvas id="planDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const planCtx = document.getElementById('planDistributionChart').getContext('2d');
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($planChart['labels']) !!},
                datasets: [{
                    data: {!! json_encode($planChart['data']) !!},
                    backgroundColor: [
                        '#4F46E5', // Indigo
                        '#10B981', // Emerald
                        '#F59E0B', // Amber
                        '#EF4444', // Red
                        '#6366F1'
                    ],
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
    });
</script>
@endsection
