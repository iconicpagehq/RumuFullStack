@extends('layouts.admin')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - RUMU App Admin')
@section('page-title', 'Invoice Details')
@section('page-subtitle', $invoice->invoice_number)

@section('header-actions')
    <div class="flex gap-2">
        <a href="{{ route('admin.billing.invoices') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded-lg transition text-sm">
            Back to List
        </a>
    </div>
@endsection

@section('admin-content')
    <div class="max-w-5xl mx-auto">
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm">
                {{ session('success') }}
            </div>
        @endif
        
        @error('resend')
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                {{ $message }}
            </div>
        @enderror

        <!-- Actions Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span class="px-3 py-1 text-sm font-semibold rounded-full 
                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 
                       ($invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                    {{ ucfirst($invoice->status) }}
                </span>
                @if($invoice->emailed_at)
                    <span class="text-sm text-gray-500 flex items-center">
                        <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Last Emailed: {{ $invoice->emailed_at->format('M d, Y h:i A') }}
                    </span>
                @endif
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.billing.invoices.preview', $invoice) }}" target="_blank" class="text-gray-600 hover:text-gray-900 flex items-center font-medium text-sm bg-gray-50 hover:bg-gray-100 py-2 px-4 rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print
                </a>
                
                <a href="{{ route('admin.billing.invoices.download', $invoice) }}" class="text-gray-600 hover:text-gray-900 flex items-center font-medium text-sm bg-gray-50 hover:bg-gray-100 py-2 px-4 rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download PDF
                </a>
                
                <form action="{{ route('admin.billing.invoices.resend', $invoice) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-blue-600 hover:text-blue-900 flex items-center font-medium text-sm bg-blue-50 hover:bg-blue-100 py-2 px-4 rounded-lg transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Resend Email
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Customer Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Customer Information</h3>
                @if($invoice->user)
                    <div class="space-y-3">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Name</span>
                            <span class="text-sm font-medium text-gray-900">{{ $invoice->user->name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Email</span>
                            <span class="text-sm text-gray-900">{{ $invoice->user->email }}</span>
                        </div>
                        @if($invoice->user->company)
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase">Company</span>
                                <span class="text-sm text-gray-900">{{ $invoice->user->company }}</span>
                            </div>
                        @endif
                        <div class="pt-2">
                            <a href="{{ route('admin.realtors.billing', $invoice->user) }}" class="text-sm text-primary hover:underline">View Customer Billing &rarr;</a>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Customer account has been deleted.</p>
                @endif
            </div>

            <!-- Subscription & Payment -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Subscription & Payment</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Plan</span>
                            <span class="text-sm font-medium text-gray-900">{{ $invoice->subscription->plan_name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Duration</span>
                            <span class="text-sm text-gray-900">{{ $invoice->subscription->duration_months ?? 0 }} Months</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Sub. Status</span>
                            <span class="text-sm text-gray-900">{{ ucfirst($invoice->subscription->status ?? 'Unknown') }}</span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Method</span>
                            <span class="text-sm text-gray-900">{{ $invoice->payment ? ucfirst(str_replace('_', ' ', $invoice->payment->payment_method)) : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Transaction ID</span>
                            <span class="text-sm text-gray-900">{{ $invoice->payment->transaction_id ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Pay Date</span>
                            <span class="text-sm text-gray-900">{{ $invoice->payment ? $invoice->payment->payment_date->format('M d, Y') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items & Totals -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Invoice Items</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($invoice->invoiceItems as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="w-full md:w-1/2 ml-auto space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal:</span>
                        <span class="font-medium text-gray-900">{{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->discount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Discount:</span>
                            <span class="font-medium text-red-600">-{{ number_format($invoice->discount, 2) }}</span>
                        </div>
                    @endif
                    @if($invoice->tax > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">GST:</span>
                            <span class="font-medium text-gray-900">{{ number_format($invoice->tax, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-200">
                        <span class="text-gray-900">Grand Total:</span>
                        <span class="text-primary">{{ $invoice->payment ? $invoice->payment->currency : 'INR' }} {{ number_format($invoice->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Metadata -->
        <div class="text-center text-xs text-gray-400">
            Invoice created on {{ $invoice->created_at->format('M d, Y h:i A') }} &bull; 
            Last updated {{ $invoice->updated_at->format('M d, Y h:i A') }}
        </div>
    </div>
@endsection
