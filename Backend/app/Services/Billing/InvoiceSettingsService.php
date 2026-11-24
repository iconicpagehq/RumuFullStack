<?php

namespace App\Services\Billing;

class InvoiceSettingsService
{
    protected $settings;

    public function __construct(BillingSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function getPrefix(): string
    {
        return $this->settings->get('invoice_prefix', 'INV-');
    }

    public function getStartNumber(): int
    {
        return $this->settings->get('invoice_start_number', 1000);
    }

    public function getFooter(): string
    {
        return $this->settings->get('invoice_footer', 'Thank you for your business.');
    }

    public function getPaymentTerms(): string
    {
        return $this->settings->get('invoice_payment_terms', 'Net 15');
    }

    public function getCurrency(): string
    {
        return $this->settings->get('invoice_currency', 'USD');
    }
}
