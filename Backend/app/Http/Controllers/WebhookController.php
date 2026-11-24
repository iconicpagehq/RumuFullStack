<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Payments\PaymentManager;
use App\Services\SubscriptionActivationService;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming Razorpay webhooks.
     */
    public function handleRazorpay(Request $request, PaymentManager $paymentManager, SubscriptionActivationService $activationService)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if (!$signature) {
            Log::warning('Razorpay webhook missing signature.');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        $gateway = $paymentManager->gateway();
        
        // 1. Verify Signature
        if (!$gateway->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Razorpay webhook signature mismatch.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // 2. Process Event
        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';

        Log::info("Razorpay webhook received: {$event}", ['webhook_id' => $request->header('X-Razorpay-Event-Id')]);

        if ($event === 'order.paid' || $event === 'payment.captured') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? null;
            $orderId = $paymentEntity['order_id'] ?? null;

            if ($orderId) {
                $payment = Payment::where('gateway_order_id', $orderId)->first();

                if ($payment) {
                    // Update Gateway IDs
                    if ($payment->payment_status !== 'completed') {
                        $payment->update([
                            'gateway_payment_id' => $paymentEntity['id'],
                        ]);
                        
                        // Idempotent Activation
                        $activationService->activateVerifiedSubscription($payment, $data);
                    } else {
                        Log::info('Duplicate webhook ignored safely (already completed).', [
                            'gateway_order_id' => $orderId
                        ]);
                    }
                } else {
                    Log::error('Webhook payload order_id not found in DB', ['order_id' => $orderId]);
                }
            }
        } elseif ($event === 'payment.failed') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? null;
            $orderId = $paymentEntity['order_id'] ?? null;

            if ($orderId) {
                $payment = Payment::where('gateway_order_id', $orderId)->first();
                if ($payment && $payment->payment_status === 'pending') {
                    $payment->update(['payment_status' => 'failed', 'webhook_payload' => json_encode($data)]);
                    Log::info('Payment marked as failed via webhook', ['payment_id' => $payment->id]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
