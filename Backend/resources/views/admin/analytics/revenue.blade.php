@extends('layouts.admin')

@section('title', 'Revenue Analytics - RUMU Admin')
@section('page-title', 'Revenue Analytics')
@section('page-subtitle', 'Detailed revenue trends and historical analysis')

@section('admin-content')
<div class="space-y-6">
    <!-- Top KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Revenue</h4>
            <div class="text-2xl font-bold text-gray-900 group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['total'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['total'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 border-b-4 border-b-primary">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">MRR</h4>
            <div class="text-2xl font-bold text-primary group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['mrr'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['mrr'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">ARR</h4>
            <div class="text-2xl font-bold text-gray-900 group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['arr'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['arr'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">ARPC</h4>
            <div class="text-2xl font-bold text-gray-900 group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['arpc'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['arpc'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-1">Average Revenue Per Customer</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-bold text-gray-900 mb-4">Daily Revenue (Last 30 Days)</h3>
            <div class="relative h-[300px] w-full">
                <canvas id="dailyRevenueChart"></canvas>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-bold text-gray-900 mb-4">Monthly Revenue (Last 12 Months)</h3>
            <div class="relative h-[300px] w-full">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function formatIndianCurrency(number, currencyCode = 'INR') {
        let symbol = currencyCode === 'INR' ? '₹' : currencyCode + ' ';
        let isNegative = number < 0;
        number = Math.abs(number);
        let formatted = '';

        if (number >= 10000000) {
            formatted = (number / 10000000).toFixed(2).replace(/\.?0+$/, '') + 'Cr';
        } else if (number >= 100000) {
            formatted = (number / 100000).toFixed(2).replace(/\.?0+$/, '') + 'L';
        } else if (number >= 1000) {
            formatted = (number / 1000).toFixed(2).replace(/\.?0+$/, '') + 'K';
        } else {
            formatted = number.toFixed(2);
        }
        return (isNegative ? '-' : '') + symbol + formatted;
    }

    function formatIndianCurrencyTooltip(number, currencyCode = 'INR') {
        let symbol = currencyCode === 'INR' ? '₹' : currencyCode + ' ';
        let isNegative = number < 0;
        number = Math.abs(number);
        
        let formatter = new Intl.NumberFormat('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        return (isNegative ? '-' : '') + symbol + formatter.format(number);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyChart['labels']) !!},
                datasets: [{
                    label: 'Revenue',
                    data: {!! json_encode($dailyChart['data']) !!},
                    borderColor: '#4F46E5', // primary color
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4F46E5',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.3,
                    cubicInterpolationMode: 'monotone'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: { 
                        min: 0,
                        beginAtZero: true,
                        suggestedMax: 1000,
                        grace: '10%',
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                return formatIndianCurrency(value, '{{ config('payments.currency', 'INR') }}');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatIndianCurrencyTooltip(context.parsed.y, '{{ config('payments.currency', 'INR') }}');
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($monthlyChart['labels']) !!},
                datasets: [{
                    label: 'Revenue',
                    data: {!! json_encode($monthlyChart['data']) !!},
                    backgroundColor: '#4F46E5',
                    borderRadius: 4,
                    maxBarThickness: 40,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: { 
                        min: 0,
                        beginAtZero: true,
                        suggestedMax: 10000,
                        grace: '10%',
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                return formatIndianCurrency(value, '{{ config('payments.currency', 'INR') }}');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatIndianCurrencyTooltip(context.parsed.y, '{{ config('payments.currency', 'INR') }}');
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
