<?php

namespace App\Services\Billing;

class TaxSettingsService
{
    protected $settings;

    public function __construct(BillingSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function isEnabled(): bool
    {
        return $this->settings->get('tax_enabled', true);
    }

    public function getDefaultGstPercent(): float
    {
        return $this->settings->get('tax_default_gst_percent', 18.0);
    }

    public function getTaxLabel(): string
    {
        return $this->settings->get('tax_label', 'GST');
    }

    public function isInclusive(): bool
    {
        return $this->settings->get('tax_inclusive', false);
    }
}
