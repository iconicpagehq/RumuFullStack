<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Razorpay\RazorpayService;
use Exception;

class PaymentManager
{
    /**
     * Resolve and return the active payment gateway service.
     */
    public function gateway(): PaymentGatewayInterface
    {
        $default = config('payments.default');

        if ($default === 'razorpay') {
            return new RazorpayService();
        }

        throw new Exception("Payment gateway [{$default}] is not supported.");
    }
}
