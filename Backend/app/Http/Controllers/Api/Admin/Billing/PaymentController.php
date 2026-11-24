<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,completed,failed,refunded',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Payment::with(['subscription.user', 'coupon']);

        if ($status = $request->input('status')) {
            $query->where('payment_status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                    ->orWhere('gateway_order_id', 'like', "%{$search}%")
                    ->orWhereHas('subscription.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated(PaymentResource::collection($payments));
    }

    public function show(Payment $payment)
    {
        $payment->load(['subscription.user', 'coupon']);

        return $this->success(new PaymentResource($payment));
    }
}
