<?php

namespace App\Support;

use App\Models\Setting;

class MetaCapiSettings
{
    private const KEY_ENABLED = 'meta_capi_enabled';

    public static function isEnabled(): bool
    {
        return Setting::getBool(self::KEY_ENABLED, false);
    }

    public static function setEnabled(bool $enabled): void
    {
        Setting::set(self::KEY_ENABLED, $enabled);
    }
}
