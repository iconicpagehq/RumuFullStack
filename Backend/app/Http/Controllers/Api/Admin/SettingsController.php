<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Setting;
use App\Support\MetaCapiSettings;
use App\Services\MetaCapiService;
use Illuminate\Http\Request;

class SettingsController extends ApiController
{
    private const GENERAL_KEYS = [
        'site_name',
        'support_email',
        'support_phone',
        'default_timezone',
        'maintenance_mode',
    ];

    public function index(Request $request)
    {
        $settings = [
            'site_name' => Setting::get('site_name', config('app.name')),
            'support_email' => Setting::get('support_email', config('mail.from.address')),
            'support_phone' => Setting::get('support_phone', ''),
            'default_timezone' => Setting::get('default_timezone', config('app.timezone')),
            'maintenance_mode' => Setting::getBool('maintenance_mode', false),
        ];

        return $this->success([
            'general' => $settings,
            'appearance' => [
                'theme' => $request->user()->theme ?? 'auto',
            ],
            'meta_capi' => [
                'enabled' => MetaCapiSettings::isEnabled(),
                'configured' => app(MetaCapiService::class)->isConfigured(),
            ],
            'system' => [
                'app_name' => config('app.name'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'support_email' => 'sometimes|nullable|email|max:255',
            'support_phone' => 'sometimes|nullable|string|max:30',
            'default_timezone' => 'sometimes|string|max:64',
            'maintenance_mode' => 'sometimes|boolean',
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, self::GENERAL_KEYS, true)) {
                Setting::set($key, $value);
            }
        }

        return $this->success(null, 'Settings updated.');
    }

    public function updateAppearance(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,auto',
        ]);

        $request->user()->update([
            'theme' => $validated['theme'],
        ]);

        return $this->success(['theme' => $validated['theme']], 'Theme updated.');
    }

    public function updateMetaCapi(Request $request)
    {
        $validated = $request->validate([
            'meta_capi_enabled' => 'sometimes|boolean',
        ]);

        MetaCapiSettings::setEnabled((bool) ($validated['meta_capi_enabled'] ?? false));

        return $this->success(['enabled' => MetaCapiSettings::isEnabled()], 'Meta Conversions API setting saved.');
    }
}
