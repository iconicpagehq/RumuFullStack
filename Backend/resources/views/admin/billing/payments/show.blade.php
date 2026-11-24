@extends('layouts.admin')

@section('title', 'Payment Details - RUMU App Admin')
@section('page-title', 'Payment Details')
@section('page-subtitle', 'Transaction breakdown and timeline')

@section('header-actions')
    <a href="{{ route('admin.billing.payments') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-4 py-2">
        Back to Payments
    </a>
@endsection

@section('admin-content')
<div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Payment Info -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800">Transaction #{{ $payment->id }}</h3>
                <span class="px-3 py-1 text-xs font-bold rounded-full 
                    {{ $payment->payment_status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->payment_status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ strtoupper($payment->payment_status) }}
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Amount</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Gateway</div>
                        <div class="mt-1 text-base font-medium text-gray-900">{{ ucfirst($payment->gateway ?? 'Manual') }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Gateway Order ID</div>
                        <div class="mt-1 text-sm font-mono text-gray-600">{{ $payment->gateway_order_id ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Gateway Payment ID</div>
                        <div class="mt-1 text-sm font-mono text-gray-600">{{ $payment->gateway_payment_id ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Verification Status</div>
                        <div class="mt-1 text-sm text-gray-900">
                            @if($payment->verified_at)
                                <span class="text-green-600 font-semibold inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Verified on {{ $payment->verified_at->format('M d, Y h:i A') }}
                                </span>
                            @else
                                <span class="text-yellow-600 font-semibold inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Unverified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($payment->webhook_payload)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800">Raw Webhook Payload</h3>
            </div>
            <div class="p-6">
                <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto font-mono whitespace-pre-wrap">{{ json_encode(json_decode($payment->webhook_payload), JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
        @endif
    </div>

    <!-- Timeline & Details -->
    <div class="space-y-6">
        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800">Customer</h3>
            </div>
            <div class="p-6">
                @if($payment->subscription && $payment->subscription->user)
                    <div class="font-medium text-gray-900">{{ $payment->subscription->user->name }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ $payment->subscription->user->email }}</div>
                    <a href="{{ route('admin.realtors.billing', $payment->subscription->user) }}" class="text-primary hover:underline text-sm inline-block mt-3">View Profile</a>
                @else
                    <div class="text-gray-500 italic">User data unavailable</div>
                @endif
            </div>
        </div>

        <!-- Subscription Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800">Subscription</h3>
            </div>
            <div class="p-6">
                @if($payment->subscription)
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">Plan</span>
                        <span class="font-bold text-gray-900">{{ $payment->subscription->plan_name }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">Status</span>
                        <span class="text-sm font-semibold {{ $payment->subscription->status === 'active' ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ ucfirst($payment->subscription->status) }}
                        </span>
                    </div>
                    @if($payment->subscription->activated_at)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Activated</span>
                        <span class="text-sm text-gray-900">{{ $payment->subscription->activated_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                @else
                    <div class="text-gray-500 italic">Subscription data unavailable</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
