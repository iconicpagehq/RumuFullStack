@extends('layouts.admin')

@section('title', 'Payment Analytics - RUMU Admin')
@section('page-title', 'Payment Analytics')
@section('page-subtitle', 'Payment status distribution and gateway usage')

@section('admin-content')
<div class="space-y-6">
    <!-- Top KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Successful Payments</h4>
            <div class="text-2xl font-bold text-green-600">{{ number_format($kpis['successful']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Failed Payments</h4>
            <div class="text-2xl font-bold text-red-600">{{ number_format($kpis['failed']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pending Payments</h4>
            <div class="text-2xl font-bold text-yellow-600">{{ number_format($kpis['pending']) }}</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-bold text-gray-900 mb-4">Payment Gateway Distribution</h3>
            <div class="w-full flex justify-center">
                <div style="width: 300px; height: 300px;">
                    <canvas id="gatewayChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gatewayCtx = document.getElementById('gatewayChart').getContext('2d');
        new Chart(gatewayCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($gatewayChart['labels']) !!},
                datasets: [{
                    data: {!! json_encode($gatewayChart['data']) !!},
                    backgroundColor: [
                        '#3B82F6', // Blue
                        '#8B5CF6', // Purple
                        '#EC4899', // Pink
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
