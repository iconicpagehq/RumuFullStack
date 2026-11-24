<?php

namespace App\Services\Payments\Razorpay;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Log;

class RazorpayService implements PaymentGatewayInterface
{
    protected Api $api;
    protected string $webhookSecret;

    public function __construct()
    {
        $keyId = config('payments.gateways.razorpay.key_id');
        $keySecret = config('payments.gateways.razorpay.key_secret');
        $this->webhookSecret = config('payments.gateways.razorpay.webhook_secret');
        
        $this->api = new Api($keyId, $keySecret);
    }

    public function createOrder(float $amount, string $currency, string $receiptId, array $notes = []): array
    {
        // Razorpay expects amount in subunits (paise for INR, cents for USD)
        $amountInSubunits = (int) round($amount * 100);

        $orderData = [
            'receipt'         => $receiptId,
            'amount'          => $amountInSubunits,
            'currency'        => strtoupper($currency),
            'payment_capture' => 1, // Auto capture
            'notes'           => $notes
        ];

        try {
            $razorpayOrder = $this->api->order->create($orderData);

            return [
                'gateway_order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay createOrder failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function verifyPayment(array $attributes): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $attributes['gateway_order_id'],
                'razorpay_payment_id' => $attributes['gateway_payment_id'],
                'razorpay_signature' => $attributes['gateway_signature']
            ]);
            
            return true;
        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay sync signature verification failed', [
                'error' => $e->getMessage(),
                'attributes' => $attributes
            ]);
            return false;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $this->api->utility->verifyWebhookSignature($payload, $signature, $this->webhookSecret);
            return true;
        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
