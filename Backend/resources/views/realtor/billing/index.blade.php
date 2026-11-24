@extends('layouts.app')

@section('title', 'Billing & Subscription - RUMU CRM')

@section('header-title', 'Billing & Subscription')
@section('header-subtitle', 'Manage your subscription, payments, and invoices.')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('realtor.partials.sidebar')

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <!-- Content Area -->
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="animate-fade-in max-w-7xl mx-auto space-y-8">
                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-6">
                            <p class="text-red-800 text-sm sm:text-base">{{ session('error') }}</p>
                        </div>
                    @endif

    <!-- Current Subscription Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Current Subscription</h3>
            @if($currentSubscription)
                <span class="px-3 py-1 text-xs font-bold rounded-full 
                    {{ $currentSubscription->status === 'active' ? 'bg-green-100 text-green-800' : ($currentSubscription->status === 'expired' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ strtoupper($currentSubscription->status) }}
                </span>
            @endif
        </div>
        <div class="p-6">
            @if($currentSubscription)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Plan</div>
                        <div class="mt-1 text-xl font-bold text-primary">{{ $currentSubscription->plan_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Started On</div>
                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $currentSubscription->start_date ? $currentSubscription->start_date->format('M d, Y') : 'Pending' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Expires On</div>
                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $currentSubscription->expiry_date ? $currentSubscription->expiry_date->format('M d, Y') : 'N/A' }}</div>
                    </div>
                </div>

                <div class="mt-6 flex gap-4 border-t border-gray-100 pt-6">
                    @if($currentSubscription->status !== 'active')
                        <a href="{{ route('checkout.show') }}" class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium transition text-sm">
                            Renew Subscription
                        </a>
                    @endif
                    @if(strtolower($currentSubscription->plan_name) !== 'pro')
                        <button class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-6 py-2 rounded-lg font-medium transition text-sm">
                            Upgrade Plan
                        </button>
                    @endif
                </div>
            @else
                <div class="text-center py-6">
                    <p class="text-gray-500 mb-4">You do not have an active subscription.</p>
                    <a href="{{ route('checkout.show') }}" class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium transition inline-block">
                        Subscribe Now
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- History Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Payment History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Payment History</h3>
            </div>
            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($payments as $payment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : $payment->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-6 py-3 text-sm text-right">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                                            {{ $payment->payment_status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->payment_status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ ucfirst($payment->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())
                    <div class="px-6 py-3 border-t border-gray-100">
                        {{ $payments->links() }}
                    </div>
                @endif
            @else
                <div class="p-6 text-center text-sm text-gray-500">No payment history available.</div>
            @endif
        </div>

        <!-- Invoice History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Invoices</h3>
            </div>
            @if($invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm">
                                        <a href="{{ route('realtor.billing.invoice.show', $invoice) }}" class="font-medium text-primary hover:underline">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->created_at->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                        {{ number_format($invoice->total, 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right">
                                        <a href="{{ route('realtor.billing.invoice.download', $invoice) }}" class="text-primary hover:text-primary/80 inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div class="px-6 py-3 border-t border-gray-100">
                        {{ $invoices->links() }}
                    </div>
                @endif
            @else
                <div class="p-6 text-center text-sm text-gray-500">No invoices generated yet.</div>
            @endif
        </div>


    </div>
                </div>
            </div>
        </main>
    </div>
@endsection
