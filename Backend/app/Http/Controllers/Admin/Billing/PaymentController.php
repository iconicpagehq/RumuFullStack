<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of all payments.
     */
    public function index()
    {
        $payments = Payment::with('subscription.user')->latest()->paginate(20);
        return view('admin.billing.payments.index', compact('payments'));
    }

    /**
     * Display the specified payment and its gateway details.
     */
    public function show(Payment $payment)
    {
        $payment->load(['subscription.user']);
        return view('admin.billing.payments.show', compact('payment'));
    }
}
