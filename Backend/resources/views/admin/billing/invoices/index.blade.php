@extends('layouts.admin')

@section('title', 'Manage Invoices - RUMU App Admin')
@section('page-title', 'Invoices')
@section('page-subtitle', 'Manage and track customer invoices')

@section('admin-content')
    <div class="max-w-7xl mx-auto">
        <!-- Filters & Search -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <form action="{{ route('admin.billing.invoices') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Invoice #, Customer Name, Email, or TXN ID" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                            <option value="">All Statuses</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- Plan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                        <select name="plan" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                            <option value="">All Plans</option>
                            @foreach(config('billing.plans') as $plan)
                                <option value="{{ $plan['name'] }}" {{ request('plan') == $plan['name'] ? 'selected' : '' }}>{{ $plan['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-primary focus:border-primary text-sm">
                            <option value="">All Methods</option>
                            <option value="credit_card" {{ request('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                            <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="upi" {{ request('payment_method') == 'upi' ? 'selected' : '' }}>UPI</option>
                            <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                            Apply Filters
                        </button>
                        <a href="{{ route('admin.billing.invoices') }}" class="w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition text-sm">
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            @if($invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="text-sm font-medium text-primary hover:underline">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($invoice->user)
                                            <div class="text-sm font-medium text-gray-900">{{ $invoice->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $invoice->user->email }}</div>
                                        @else
                                            <span class="text-sm text-gray-500">Deleted User</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $invoice->subscription->plan_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $invoice->payment ? $invoice->payment->currency : 'INR' }} {{ number_format($invoice->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                        @if($invoice->emailed_at)
                                            <div class="text-xs text-gray-400 mt-1">Emailed</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $invoice->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right space-x-2">
                                        <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                        <a href="{{ route('admin.billing.invoices.download', $invoice) }}" class="text-gray-600 hover:text-gray-900">PDF</a>
                                        <form action="{{ route('admin.billing.invoices.resend', $invoice) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-900">Resend</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $invoices->links() }}
                </div>
            @else
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-medium">No invoices found matching your filters.</p>
                    <a href="{{ route('admin.billing.invoices') }}" class="text-primary hover:underline text-sm mt-2 inline-block">Clear all filters</a>
                </div>
            @endif
        </div>
    </div>
@endsection
