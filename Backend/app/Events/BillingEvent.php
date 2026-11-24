<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Invoice;

class BillingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $subscription;
    public $payment;
    public $invoice;

    public function __construct(User $user, ?Subscription $subscription = null, ?Payment $payment = null, ?Invoice $invoice = null)
    {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->payment = $payment;
        $this->invoice = $invoice;
    }
}
