@extends('layouts.admin')

@section('title', 'Analytics Dashboard - RUMU Admin')
@section('page-title', 'Executive Analytics')
@section('page-subtitle', 'Real-time overview of business performance')

@section('admin-content')
<div class="space-y-6">
    <!-- Top Row: Revenue KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Revenue</h4>
            <div class="text-3xl font-bold text-gray-900 group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['total_revenue'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['total_revenue'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">MRR (Monthly Recurring Revenue)</h4>
            <div class="text-3xl font-bold text-primary group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['mrr'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['mrr'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-2">Revenue generated in the last 30 days</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center border-b-4 border-b-primary">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">ARR (Annual Recurring Revenue)</h4>
            <div class="text-3xl font-bold text-gray-900 group relative inline-block cursor-help w-max">
                <span class="border-b border-dashed border-gray-400">{{ format_indian_currency($kpis['arr'], config('payments.currency')) }}</span>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-3 py-1.5 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-active:opacity-100 shadow-xl font-normal tracking-normal">
                    {{ format_indian_currency_tooltip($kpis['arr'], config('payments.currency')) }}
                    <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"></div>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-2">Projected MRR × 12</div>
        </div>
    </div>

    <!-- Second Row: Customer & Subscription KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Customers</h4>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($kpis['total_customers']) }}</div>
            <div class="text-sm font-medium text-green-600 mt-2">+{{ $kpis['new_customers_month'] }} this month</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Active Customers</h4>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($kpis['active_customers']) }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Active Subscriptions</h4>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($kpis['active_subscriptions']) }}</div>
            <div class="text-sm font-medium text-green-600 mt-2">+{{ $kpis['new_subscriptions_month'] }} this month</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Expired Subscriptions</h4>
            <div class="text-2xl font-bold text-red-600">{{ number_format($kpis['expired_subscriptions']) }}</div>
        </div>
    </div>

    <!-- Third Row: Operational KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Successful Payments</h4>
            <div class="text-2xl font-bold text-green-600">{{ number_format($kpis['successful_payments']) }}</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Failed Payments</h4>
            <div class="text-2xl font-bold text-red-600">{{ number_format($kpis['failed_payments']) }}</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pending Payments</h4>
            <div class="text-2xl font-bold text-yellow-600">{{ number_format($kpis['pending_payments']) }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Invoices</h4>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($kpis['total_invoices']) }}</div>
        </div>
    </div>
</div>
@endsection
