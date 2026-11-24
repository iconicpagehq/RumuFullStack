@extends('layouts.admin')

@section('title', 'Manage Billing - ' . $realtor->name)
@section('page-title', 'Billing: ' . $realtor->name)
@section('page-subtitle', 'Manage subscriptions and payments')

@section('header-actions')
    <div class="flex gap-2">
        <a href="{{ route('admin.realtors.show', $realtor) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded-lg transition text-sm">
            Back to Profile
        </a>
    </div>
@endsection

@section('admin-content')
    <div class="max-w-7xl mx-auto">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: History -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Info Summary -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xl">
                            {{ strtoupper(substr($realtor->name, 0, 1)) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">{{ $realtor->name }}</h2>
                            <p class="text-gray-500 text-sm">{{ $realtor->email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Subscriptions History -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-bold text-gray-800">Subscription History</h3>
                    </div>
                    @if($realtor->subscriptions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($realtor->subscriptions as $sub)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $sub->plan_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->duration_months }} Months</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ $sub->start_date->format('M d, Y') }} - 
                                                {{ $sub->expiry_date ? $sub->expiry_date->format('M d, Y') : 'Ongoing' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $sub->status === 'active' ? 'bg-green-100 text-green-800' : ($sub->status === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ ucfirst($sub->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-gray-500 text-sm">No subscriptions found.</div>
                    @endif
                </div>

                <!-- Payments History -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-bold text-gray-800">Payment History</h3>
                    </div>
                    @if($realtor->payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($realtor->payments as $payment)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : $payment->created_at->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->payment_method ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $payment->payment_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ ucfirst($payment->payment_status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-gray-500 text-sm">No payments found.</div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Assign Plan Form -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Assign New Plan</h3>
                    
                    <form action="{{ route('admin.realtors.assign-plan', $realtor) }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                            <select name="plan_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                <option value="">Select Plan...</option>
                                @foreach($plans as $key => $plan)
                                    <option value="{{ $plan['name'] }}">{{ $plan['name'] }}</option>
                                @endforeach
                            </select>
                            @error('plan_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Duration (Months)</label>
                                <input type="number" name="duration_months" value="12" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                @error('duration_months')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sub. Status</label>
                                <select name="subscription_status" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                    <option value="active" selected>Active</option>
                                    <option value="pending">Pending</option>
                                </select>
                                @error('subscription_status')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" name="start_date" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                @error('start_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                                <input type="date" name="expiry_date" value="{{ date('Y-m-d', strtotime('+1 year')) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                                @error('expiry_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Financials</h4>
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                                    <input type="number" step="0.01" name="price" value="0.00" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                    @error('price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                    <input type="text" name="currency" value="INR" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                    @error('currency')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">GST Amount</label>
                                    <input type="number" step="0.01" name="gst" value="0.00" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                                    <input type="number" step="0.01" name="discount" value="0.00" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Payment Details</h4>
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                                    <select name="payment_method" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="upi">UPI</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pay Status</label>
                                    <select name="payment_status" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" required>
                                        <option value="completed" selected>Completed</option>
                                        <option value="pending">Pending</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID (Optional)</label>
                                <input type="text" name="transaction_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
                            <textarea name="internal_notes" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm" placeholder="Admin notes..."></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-4 rounded-lg transition text-sm">
                                Assign Plan & Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
