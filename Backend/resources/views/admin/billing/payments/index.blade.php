@extends('layouts.admin')

@section('title', 'Global Payments - RUMU App Admin')
@section('page-title', 'Payments')
@section('page-subtitle', 'Manage all customer payments')

@section('admin-content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800">All Payments</h3>
            </div>
            
            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($payments as $payment)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : $payment->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4">
                                        @if($payment->subscription && $payment->subscription->user)
                                            <div class="flex items-center">
                                                <div class="ml-1">
                                                    <a href="{{ route('admin.realtors.billing', $payment->subscription->user) }}" class="text-sm font-medium text-primary hover:underline">
                                                        {{ $payment->subscription->user->name }}
                                                    </a>
                                                    <p class="text-xs text-gray-500">{{ $payment->subscription->plan_name }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500">Deleted User</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="font-medium text-gray-800">{{ ucfirst($payment->gateway ?? 'Manual') }}</div>
                                        @if($payment->gateway_payment_id)
                                            <div class="text-xs text-gray-400 font-mono mt-1">{{ $payment->gateway_payment_id }}</div>
                                        @elseif($payment->transaction_id)
                                            <div class="text-xs text-gray-400 font-mono mt-1">{{ $payment->transaction_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $payment->payment_status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->payment_status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ ucfirst($payment->payment_status) }}
                                        </span>
                                        @if($payment->verified_at)
                                            <div class="text-[10px] text-gray-400 mt-1" title="{{ $payment->verified_at }}">Verified</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <a href="{{ route('admin.billing.payments.show', $payment) }}" class="text-primary hover:text-primary/80 font-medium text-sm">View Details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    No payments found.
                </div>
            @endif
        </div>
    </div>
@endsection
