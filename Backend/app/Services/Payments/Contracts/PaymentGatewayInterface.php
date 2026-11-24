<?php

namespace App\Services\Payments\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Create an order/checkout session with the gateway.
     *
     * @param float $amount
     * @param string $currency
     * @param string $receiptId
     * @param array $notes
     * @return array Returns gateway specific order details (e.g. order_id)
     */
    public function createOrder(float $amount, string $currency, string $receiptId, array $notes = []): array;

    /**
     * Verify a payment asynchronously (webhook signature verification).
     *
     * @param string $payload Raw payload string
     * @param string $signature Signature provided by gateway
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Verify a payment synchronously (e.g. after customer returns to site).
     *
     * @param array $attributes Gateway-specific attributes needed for verification
     * @return bool
     */
    public function verifyPayment(array $attributes): bool;
}
