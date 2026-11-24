@extends('layouts.admin')

@section('title', 'Billing Settings - RUMU Admin')
@section('page-title', 'Billing & Configuration Settings')
@section('page-subtitle', 'Manage global billing rules, formatting, and behavior')

@section('admin-content')

@if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    
    <!-- Sidebar Navigation for Settings -->
    <div class="lg:col-span-1">
        <nav class="space-y-1 bg-white rounded-xl shadow-sm border border-gray-100 p-2" aria-label="Sidebar">
            <a href="#company" class="settings-tab active bg-primary/10 text-primary group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="company-panel">
                <span class="truncate">Company Profile</span>
            </a>
            <a href="#invoice" class="settings-tab text-gray-700 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="invoice-panel">
                <span class="truncate">Invoice Formats</span>
            </a>
            <a href="#tax" class="settings-tab text-gray-700 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="tax-panel">
                <span class="truncate">Taxes & Rates</span>
            </a>
            <a href="#subscription" class="settings-tab text-gray-700 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="subscription-panel">
                <span class="truncate">Subscriptions</span>
            </a>
            <a href="#payment" class="settings-tab text-gray-700 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="payment-panel">
                <span class="truncate">Payments</span>
            </a>
            <a href="#notifications" class="settings-tab text-gray-700 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="notifications-panel">
                <span class="truncate">Notifications</span>
            </a>
            <a href="#audit" class="settings-tab text-gray-700 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md" data-target="audit-panel">
                <span class="truncate">Audit Log</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-3">
        <form action="{{ route('admin.settings.billing.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            @csrf
            
            @php
                // Helper to extract value safely from grouped settings collection
                $getVal = function($key) use ($settings) {
                    foreach ($settings as $group => $items) {
                        $item = $items->firstWhere('key', $key);
                        if ($item) return $item->typed_value;
                    }
                    return null;
                };
            @endphp

            <!-- Company Profile -->
            <div id="company-panel" class="settings-panel p-6 space-y-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 border-b border-gray-200 pb-3">Company Billing Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Display Name</label>
                        <input type="text" name="company_name" value="{{ $getVal('company_name') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Legal Business Name</label>
                        <input type="text" name="company_legal_name" value="{{ $getVal('company_legal_name') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GST/VAT Number</label>
                        <input type="text" name="company_gst" value="{{ $getVal('company_gst') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PAN Number (Optional)</label>
                        <input type="text" name="company_pan" value="{{ $getVal('company_pan') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Billing Email</label>
                        <input type="email" name="company_email" value="{{ $getVal('company_email') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Billing Phone</label>
                        <input type="text" name="company_phone" value="{{ $getVal('company_phone') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Address</label>
                        <textarea name="company_address" rows="3" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">{{ $getVal('company_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Invoice Settings -->
            <div id="invoice-panel" class="settings-panel hidden p-6 space-y-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 border-b border-gray-200 pb-3">Invoice Formatting</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number Prefix</label>
                        <input type="text" name="invoice_prefix" value="{{ $getVal('invoice_prefix') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Starting Invoice Number</label>
                        <input type="number" name="invoice_start_number" value="{{ $getVal('invoice_start_number') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency (ISO Code)</label>
                        <input type="text" name="invoice_currency" value="{{ $getVal('invoice_currency') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Payment Terms</label>
                        <input type="text" name="invoice_payment_terms" value="{{ $getVal('invoice_payment_terms') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Footer / Notes</label>
                        <textarea name="invoice_footer" rows="3" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">{{ $getVal('invoice_footer') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Tax Settings -->
            <div id="tax-panel" class="settings-panel hidden p-6 space-y-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 border-b border-gray-200 pb-3">Taxes & Rates</h3>
                
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="tax_enabled" id="tax_enabled" value="1" {{ $getVal('tax_enabled') ? 'checked' : '' }} class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="tax_enabled" class="ml-2 block text-sm font-bold text-gray-900">Enable Tax Calculation globally</label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Tax Percentage (%)</label>
                        <input type="number" step="0.01" name="tax_default_gst_percent" value="{{ $getVal('tax_default_gst_percent') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Label (e.g. GST, VAT)</label>
                        <input type="text" name="tax_label" value="{{ $getVal('tax_label') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>

                <div class="flex items-center mt-4">
                    <input type="checkbox" name="tax_inclusive" id="tax_inclusive" value="1" {{ $getVal('tax_inclusive') ? 'checked' : '' }} class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="tax_inclusive" class="ml-2 block text-sm text-gray-700">Prices entered are already Tax Inclusive</label>
                </div>
            </div>

            <!-- Subscription Settings -->
            <div id="subscription-panel" class="settings-panel hidden p-6 space-y-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 border-b border-gray-200 pb-3">Subscription Lifecycle</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Trial Period (Days)</label>
                        <input type="number" name="sub_default_trial_days" value="{{ $getVal('sub_default_trial_days') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grace Period (Days)</label>
                        <input type="number" name="sub_grace_period_days" value="{{ $getVal('sub_grace_period_days') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>

                <div class="flex items-center mt-4">
                    <input type="checkbox" name="sub_auto_expire" id="sub_auto_expire" value="1" {{ $getVal('sub_auto_expire') ? 'checked' : '' }} class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="sub_auto_expire" class="ml-2 block text-sm text-gray-700">Automatically set subscriptions to 'expired' when Grace Period ends</label>
                </div>
            </div>

            <!-- Payment Settings -->
            <div id="payment-panel" class="settings-panel hidden p-6 space-y-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 border-b border-gray-200 pb-3">Payment Processing</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Payment Gateway</label>
                        <select name="payment_default_gateway" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="stripe" {{ $getVal('payment_default_gateway') == 'stripe' ? 'selected' : '' }}>Stripe</option>
                            <option value="razorpay" {{ $getVal('payment_default_gateway') == 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                            <option value="paypal" {{ $getVal('payment_default_gateway') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Retry Attempts</label>
                        <input type="number" name="payment_retry_attempts" value="{{ $getVal('payment_retry_attempts') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>

                <div class="flex items-center mt-4 p-4 bg-yellow-50 rounded border border-yellow-200">
                    <input type="checkbox" name="payment_test_mode" id="payment_test_mode" value="1" {{ $getVal('payment_test_mode') ? 'checked' : '' }} class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="payment_test_mode" class="ml-2 block text-sm font-bold text-gray-900">Enable Test Mode (Sandbox)</label>
                </div>
            </div>

            <!-- Notifications -->
            <div id="notifications-panel" class="settings-panel hidden p-6 space-y-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 border-b border-gray-200 pb-3">Automated Notifications</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="notify_invoice_email_enabled" name="notify_invoice_email_enabled" type="checkbox" value="1" {{ $getVal('notify_invoice_email_enabled') ? 'checked' : '' }} class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="notify_invoice_email_enabled" class="font-medium text-gray-700">Invoice Generation Emails</label>
                            <p class="text-gray-500">Send an email with PDF attached when a new invoice is created.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="notify_payment_success_enabled" name="notify_payment_success_enabled" type="checkbox" value="1" {{ $getVal('notify_payment_success_enabled') ? 'checked' : '' }} class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="notify_payment_success_enabled" class="font-medium text-gray-700">Payment Success Emails</label>
                            <p class="text-gray-500">Send a receipt email when a payment succeeds.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="notify_renewal_reminder_enabled" name="notify_renewal_reminder_enabled" type="checkbox" value="1" {{ $getVal('notify_renewal_reminder_enabled') ? 'checked' : '' }} class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="notify_renewal_reminder_enabled" class="font-medium text-gray-700">Subscription Expiry Reminders</label>
                            <p class="text-gray-500">Send an email before the subscription expires.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 border-t pt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Reminder Schedule (Days Before)</label>
                        <input type="number" name="reminder_sub_expiry_days" value="{{ $getVal('reminder_sub_expiry_days') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <!-- Submit Button (Sticky to bottom) -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="inline-flex justify-center items-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Audit Log Section (Read only) -->
<div id="audit-panel" class="settings-panel hidden mt-8 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Configuration Audit Log</h3>
        <p class="text-sm text-gray-500">Track changes to billing settings over time.</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setting Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Value</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($audits as $audit)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $audit->created_at->format('M d, Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                        {{ $audit->user->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                        {{ $audit->setting_key }}
                    </td>
                    <td class="px-6 py-4 text-sm text-red-600 bg-red-50/50">
                        {{ Str::limit($audit->old_value, 50) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-green-600 bg-green-50/50">
                        {{ Str::limit($audit->new_value, 50) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                        No audit records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.settings-tab');
        const panels = document.querySelectorAll('.settings-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                
                // Hide all panels EXCEPT audit if we are clicking a normal tab
                // Actually, let's just hide everything and show the target
                panels.forEach(p => p.classList.add('hidden'));
                
                // If it's audit, show audit and hide the main form
                if (targetId === 'audit-panel') {
                    document.getElementById('audit-panel').classList.remove('hidden');
                    document.querySelector('form').classList.add('hidden');
                } else {
                    document.querySelector('form').classList.remove('hidden');
                    document.getElementById(targetId).classList.remove('hidden');
                }

                // Update tab styles
                tabs.forEach(t => {
                    t.classList.remove('bg-primary/10', 'text-primary');
                    t.classList.add('text-gray-700');
                });
                this.classList.remove('text-gray-700');
                this.classList.add('bg-primary/10', 'text-primary');
            });
        });
    });
</script>
@endsection
