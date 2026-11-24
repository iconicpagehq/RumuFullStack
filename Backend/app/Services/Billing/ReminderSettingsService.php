<?php

namespace App\Services\Billing;

class ReminderSettingsService
{
    protected $settings;

    public function __construct(BillingSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function getSubscriptionExpiryReminderDays(): int
    {
        return $this->settings->get('reminder_sub_expiry_days', 7);
    }
}
