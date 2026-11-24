<?php

namespace App\Services\Billing;

use App\Models\BillingNotification;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Invoice;
use App\Mail\BillingNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BillingNotificationService
{
    protected $templateService;
    protected $settingsService;

    public function __construct(BillingTemplateService $templateService, BillingSettingsService $settingsService)
    {
        $this->templateService = $templateService;
        $this->settingsService = $settingsService;
    }

    /**
     * Dispatch a billing notification.
     */
    public function notify(
        User $user,
        string $type,
        ?Subscription $subscription = null,
        ?Payment $payment = null,
        ?Invoice $invoice = null,
        string $channel = 'both'
    ) {
        try {
            // Check Notification Settings
            if (!$this->isNotificationEnabled($type)) {
                Log::info("Skipping notification {$type} because it is disabled in settings.");
                return;
            }

            // 1. Prepare Data for placeholders
            $data = $this->prepareTemplateData($user, $subscription, $payment, $invoice);

            // 2. Parse Template
            $parsed = $this->templateService->parseTemplate($type, $data);
            
            if (empty($parsed['subject']) || empty($parsed['body'])) {
                Log::warning("Skipping notification dispatch due to missing template for type: {$type}");
                return;
            }

            // 3. Create Notification Record
            $notification = BillingNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'subscription_id' => $subscription ? $subscription->id : null,
                'payment_id' => $payment ? $payment->id : null,
                'invoice_id' => $invoice ? $invoice->id : null,
                'channel' => $channel,
                'status' => 'pending',
            ]);

            // 4. Dispatch Email if required
            if (in_array($channel, ['email', 'both'])) {
                $this->sendEmail($notification, $user->email, $parsed['subject'], $parsed['body']);
            }

            // In-app notifications don't require an active push unless using WebSockets. 
            // They just exist in DB for the user to read.
            if ($channel === 'in_app') {
                $notification->update(['status' => 'sent', 'sent_at' => now()]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to process billing notification: " . $e->getMessage());
        }
    }

    protected function sendEmail(BillingNotification $notification, string $email, string $subject, string $bodyHtml)
    {
        try {
            Mail::to($email)->send(new BillingNotificationMail($notification, $subject, $bodyHtml));
            $notification->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Exception $e) {
            Log::error("Failed to send billing email notification: " . $e->getMessage());
            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function prepareTemplateData(User $user, ?Subscription $subscription, ?Payment $payment, ?Invoice $invoice): array
    {
        return [
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'plan_name' => $subscription ? $subscription->plan_name : 'N/A',
            'subscription_start' => $subscription && $subscription->start_date ? $subscription->start_date->format('Y-m-d') : 'N/A',
            'subscription_expiry' => $subscription && $subscription->expires_at ? $subscription->expires_at->format('Y-m-d') : 'N/A',
            'payment_amount' => $payment ? config('payments.currency') . ' ' . number_format($payment->amount, 2) : 'N/A',
            'payment_method' => $payment ? $payment->payment_method : 'N/A',
            'transaction_id' => $payment ? $payment->transaction_id : 'N/A',
            'invoice_number' => $invoice ? $invoice->invoice_number : 'N/A',
        ];
    }

    protected function isNotificationEnabled(string $type): bool
    {
        switch ($type) {
            case 'invoice_emailed':
            case 'invoice_generated':
                return (bool) $this->settingsService->get('notify_invoice_email_enabled', true);
            case 'payment_successful':
                return (bool) $this->settingsService->get('notify_payment_success_enabled', true);
            case 'subscription_expiring':
            case 'subscription_expired':
                return (bool) $this->settingsService->get('notify_renewal_reminder_enabled', true);
            default:
                return true;
        }
    }
}
