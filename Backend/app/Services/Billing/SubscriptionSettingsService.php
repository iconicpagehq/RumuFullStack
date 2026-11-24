<?php

namespace App\Services\Billing;

class SubscriptionSettingsService
{
    protected $settings;

    public function __construct(BillingSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function getDefaultTrialDays(): int
    {
        return $this->settings->get('sub_default_trial_days', 14);
    }

    public function getGracePeriodDays(): int
    {
        return $this->settings->get('sub_grace_period_days', 3);
    }

    public function shouldAutoExpire(): bool
    {
        return $this->settings->get('sub_auto_expire', true);
    }
}
