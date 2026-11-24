<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\InventoryFloor;
use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\InventoryUnit;
use App\Models\InventoryWing;
use App\Models\Lead;
use App\Observers\LeadObserver;
use App\Policies\CampaignPolicy;
use App\Policies\InventoryFloorPolicy;
use App\Policies\InventoryProjectPolicy;
use App\Policies\InventoryTowerPolicy;
use App\Policies\InventoryUnitPolicy;
use App\Policies\InventoryWingPolicy;
use App\Policies\LeadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Events\LeadStatusUpdated;
use App\Listeners\SendMetaCapiEvent;
use Illuminate\Support\Facades\Event;

// Billing Events
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionRenewed;
use App\Events\SubscriptionExpiring;
use App\Events\SubscriptionExpired;
use App\Events\PaymentSuccessful;
use App\Events\PaymentFailed;
use App\Events\InvoiceGenerated;
use App\Events\InvoiceEmailed;
use App\Listeners\SendBillingNotification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \App\Services\Telephony\TelephonyProvider::class,
            fn () => app(\App\Services\Telephony\TelephonyManager::class)->provider()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register policies
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(InventoryProject::class, InventoryProjectPolicy::class);
        Gate::policy(InventoryTower::class, InventoryTowerPolicy::class);
        Gate::policy(InventoryWing::class, InventoryWingPolicy::class);
        Gate::policy(InventoryFloor::class, InventoryFloorPolicy::class);
        Gate::policy(InventoryUnit::class, InventoryUnitPolicy::class);

        Lead::observe(LeadObserver::class);
        Event::listen(LeadStatusUpdated::class, SendMetaCapiEvent::class);

        // Billing Notifications
        Event::listen(SubscriptionActivated::class, SendBillingNotification::class);
        Event::listen(SubscriptionRenewed::class, SendBillingNotification::class);
        Event::listen(SubscriptionExpiring::class, SendBillingNotification::class);
        Event::listen(SubscriptionExpired::class, SendBillingNotification::class);
        Event::listen(PaymentSuccessful::class, SendBillingNotification::class);
        Event::listen(PaymentFailed::class, SendBillingNotification::class);
        Event::listen(InvoiceGenerated::class, SendBillingNotification::class);
        Event::listen(InvoiceEmailed::class, SendBillingNotification::class);
    }
}
