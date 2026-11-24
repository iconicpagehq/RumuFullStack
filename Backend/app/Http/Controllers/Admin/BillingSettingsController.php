<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingSetting;
use App\Models\BillingSettingAudit;
use App\Services\Billing\BillingSettingsService;
use Illuminate\Http\Request;

class BillingSettingsController extends Controller
{
    public function index()
    {
        // For settings UI, we might want to group them
        $settings = BillingSetting::all()->groupBy('group');
        $audits = BillingSettingAudit::with('user')->orderBy('created_at', 'desc')->take(50)->get();

        return view('admin.settings.billing', compact('settings', 'audits'));
    }

    public function update(Request $request, BillingSettingsService $settingsService)
    {
        $data = $request->except(['_token', '_method']);

        // Handle file uploads for logos/signatures
        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('billing', 'public');
            $data['company_logo'] = $path;
        }

        if ($request->hasFile('company_signature')) {
            $path = $request->file('company_signature')->store('billing', 'public');
            $data['company_signature'] = $path;
        }

        // The UI will send back many keys. Some checkboxes might not be present if unchecked.
        // We need a list of boolean fields to explicitly set to false if missing.
        $booleanKeys = [
            'tax_enabled',
            'tax_inclusive',
            'sub_auto_expire',
            'payment_test_mode',
            'notify_invoice_email_enabled',
            'notify_payment_success_enabled',
            'notify_renewal_reminder_enabled',
        ];

        foreach ($booleanKeys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = '0';
            }
        }

        foreach ($data as $key => $value) {
            // Check if key exists in DB before updating
            if (BillingSetting::where('key', $key)->exists()) {
                $settingsService->set($key, $value, auth()->id());
            }
        }

        return back()->with('success', 'Billing settings updated successfully.');
    }
}
