<?php

namespace App\Services\Telephony;

use InvalidArgumentException;

class TelephonyManager
{
    public function enabled(): bool
    {
        return (bool) config('telephony.enabled') && $this->provider()->isConfigured();
    }

    public function providerName(): string
    {
        return (string) config('telephony.provider', 'exotel');
    }

    public function provider(?string $name = null): TelephonyProvider
    {
        $name ??= $this->providerName();

        if (!config('telephony.enabled')) {
            return app(NullTelephonyProvider::class);
        }

        return match ($name) {
            'exotel' => app(ExotelTelephonyProvider::class),
            default => throw new InvalidArgumentException("Unsupported telephony provider [{$name}]."),
        };
    }
}
