<?php

namespace App\Services\Billing;

use App\Models\BillingSetting;
use App\Models\BillingSettingAudit;
use Illuminate\Support\Facades\Cache;

class BillingSettingsService
{
    const CACHE_KEY = 'billing_settings_all';

    /**
     * Get a specific setting value.
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->all();

        if (array_key_exists($key, $settings)) {
            // Need to apply type casting manually if pulling from cached simple array, 
            // but let's store the typed values in cache.
            return $settings[$key];
        }

        return $default;
    }

    /**
     * Get all settings as a key-value array.
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = BillingSetting::all();
            $mapped = [];
            foreach ($settings as $setting) {
                $mapped[$setting->key] = $setting->typed_value;
            }
            return $mapped;
        });
    }

    /**
     * Set a setting value and optionally log the audit.
     */
    public function set(string $key, $value, ?int $userId = null)
    {
        $setting = BillingSetting::where('key', $key)->first();
        $oldValue = $setting ? $setting->value : null;

        // Ensure boolean cast to string for DB storage if it's boolean
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            // We expect settings to be pre-seeded, but if not:
            $setting = BillingSetting::create([
                'key' => $key,
                'value' => $value,
                'group' => 'general',
                'type' => 'string'
            ]);
        }

        if ($oldValue !== (string)$value) {
            BillingSettingAudit::create([
                'user_id' => $userId,
                'setting_key' => $key,
                'old_value' => $oldValue,
                'new_value' => (string)$value,
            ]);
        }

        $this->flushCache();
    }

    /**
     * Clear the settings cache.
     */
    public function flushCache()
    {
        Cache::forget(self::CACHE_KEY);
    }
}
