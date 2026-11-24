<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Models\BillingSetting;
use App\Models\BillingSettingAudit;
use App\Services\Billing\BillingSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends ApiController
{
    private const BOOLEAN_KEYS = [
        'tax_enabled',
        'tax_inclusive',
        'sub_auto_expire',
        'payment_test_mode',
        'notify_invoice_email_enabled',
        'notify_payment_success_enabled',
        'notify_renewal_reminder_enabled',
    ];

    public function index()
    {
        $audits = BillingSettingAudit::with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($audit) => [
                'id' => $audit->id,
                'setting_key' => $audit->setting_key,
                'old_value' => $audit->old_value,
                'new_value' => $audit->new_value,
                'user' => $audit->user?->name,
                'created_at' => $audit->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'settings' => $this->groupedSettings(),
            'audits' => $audits,
        ]);
    }

    public function update(Request $request, BillingSettingsService $settingsService)
    {
        $data = $request->except(['_token', '_method']);

        if ($request->hasFile('company_logo')) {
            $data['company_logo'] = $request->file('company_logo')->store('billing', 'public');
        }

        if ($request->hasFile('company_signature')) {
            $data['company_signature'] = $request->file('company_signature')->store('billing', 'public');
        }

        foreach (self::BOOLEAN_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = '0';
            }
        }

        $updated = [];

        foreach ($data as $key => $value) {
            if (BillingSetting::where('key', $key)->exists()) {
                $settingsService->set($key, is_array($value) ? json_encode($value) : $value, $request->user()->id);
                $updated[] = $key;
            }
        }

        return $this->success([
            'settings' => $this->groupedSettings(),
            'updated_keys' => $updated,
        ], 'Billing settings updated.');
    }

    private function groupedSettings(): array
    {
        return BillingSetting::all()
            ->groupBy('group')
            ->map(function ($settings) {
                return $settings->mapWithKeys(fn ($setting) => [$setting->key => $setting->typed_value])->all();
            })
            ->all();
    }
}
