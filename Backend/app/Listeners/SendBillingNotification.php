<?php

namespace App\Listeners;

use App\Events\BillingEvent;
use App\Services\Billing\BillingNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class SendBillingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(BillingNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(BillingEvent $event): void
    {
        // Extract the short class name as the notification type, e.g., 'SubscriptionActivated' -> 'subscription_activated'
        $className = class_basename(get_class($event));
        $type = Str::snake($className);

        // Map events to channels if needed. For now, default to 'both'
        $channel = 'both';

        $this->notificationService->notify(
            $event->user,
            $type,
            $event->subscription,
            $event->payment,
            $event->invoice,
            $channel
        );
    }
}
