<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BillingSetting;
use Illuminate\Support\Facades\Cache;

class BillingSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Company Settings
            ['key' => 'company_name', 'value' => 'RUMU CRM', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_legal_name', 'value' => 'RUMU Solutions Pvt Ltd.', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_gst', 'value' => 'GSTIN1234567890', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_pan', 'value' => 'ABCDE1234F', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_email', 'value' => 'billing@rumucrm.com', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_phone', 'value' => '+1 234 567 8900', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_address', 'value' => '123 Business Avenue, Suite 100, Tech District, City', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_logo', 'value' => '', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_signature', 'value' => '', 'group' => 'company', 'type' => 'string'],
            
            // Invoice Settings
            ['key' => 'invoice_prefix', 'value' => 'RUMU-INV-', 'group' => 'invoice', 'type' => 'string'],
            ['key' => 'invoice_start_number', 'value' => '1000', 'group' => 'invoice', 'type' => 'integer'],
            ['key' => 'invoice_footer', 'value' => 'Thank you for your business. Please remit payment within 15 days.', 'group' => 'invoice', 'type' => 'string'],
            ['key' => 'invoice_payment_terms', 'value' => 'Net 15', 'group' => 'invoice', 'type' => 'string'],
            ['key' => 'invoice_currency', 'value' => 'USD', 'group' => 'invoice', 'type' => 'string'],
            
            // Tax Settings
            ['key' => 'tax_enabled', 'value' => '1', 'group' => 'tax', 'type' => 'boolean'],
            ['key' => 'tax_default_gst_percent', 'value' => '18', 'group' => 'tax', 'type' => 'float'],
            ['key' => 'tax_label', 'value' => 'GST', 'group' => 'tax', 'type' => 'string'],
            ['key' => 'tax_inclusive', 'value' => '0', 'group' => 'tax', 'type' => 'boolean'],
            
            // Subscription Settings
            ['key' => 'sub_default_trial_days', 'value' => '14', 'group' => 'subscription', 'type' => 'integer'],
            ['key' => 'sub_grace_period_days', 'value' => '3', 'group' => 'subscription', 'type' => 'integer'],
            ['key' => 'sub_auto_expire', 'value' => '1', 'group' => 'subscription', 'type' => 'boolean'],
            
            // Payment Settings
            ['key' => 'payment_default_gateway', 'value' => 'stripe', 'group' => 'payment', 'type' => 'string'],
            ['key' => 'payment_test_mode', 'value' => '1', 'group' => 'payment', 'type' => 'boolean'],
            ['key' => 'payment_retry_attempts', 'value' => '3', 'group' => 'payment', 'type' => 'integer'],
            
            // Notification Settings
            ['key' => 'notify_invoice_email_enabled', 'value' => '1', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_payment_success_enabled', 'value' => '1', 'group' => 'notification', 'type' => 'boolean'],
            ['key' => 'notify_renewal_reminder_enabled', 'value' => '1', 'group' => 'notification', 'type' => 'boolean'],
            
            // Reminders
            ['key' => 'reminder_sub_expiry_days', 'value' => '7', 'group' => 'reminder', 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            BillingSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Clear cache after seeding
        Cache::forget('billing_settings_all');
    }
}
