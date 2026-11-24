<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\RealtorDetailResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RealtorController extends ApiController
{
    public function index(Request $request)
    {
        $query = User::where('role', 'realtor')
            ->with(['plan', 'suspendedPlan'])
            ->withCount('campaigns');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($request->has('suspended')) {
            $request->boolean('suspended')
                ? $query->whereNotNull('subscription_suspended_at')
                : $query->whereNull('subscription_suspended_at');
        }

        $realtors = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated(UserResource::collection($realtors));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'plan_id' => 'required|exists:plans,id',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
        ]);

        $realtor = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'plan_id' => $validated['plan_id'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'role' => 'realtor',
        ]);

        return $this->success(
            new UserResource($realtor->load('plan')),
            'Realtor created.',
            201
        );
    }

    public function show(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        $realtor->load(['plan', 'suspendedPlan', 'campaigns', 'subscriptions', 'payments', 'invoices'])
            ->loadCount(['campaigns']);

        return $this->success(new RealtorDetailResource($realtor));
    }

    public function update(Request $request, User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($realtor->id)],
            'plan_id' => 'required|exists:plans,id',
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
        ]);

        $realtor->name = $validated['name'];
        $realtor->email = $validated['email'];
        $realtor->plan_id = $validated['plan_id'];
        $realtor->phone = $validated['phone'] ?? $realtor->phone;
        $realtor->company = $validated['company'] ?? $realtor->company;

        if (!empty($validated['password'])) {
            $realtor->password = Hash::make($validated['password']);
        }

        $realtor->save();

        return $this->success(new RealtorDetailResource($realtor->fresh()->load('plan')), 'Realtor updated.');
    }

    public function destroy(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        $realtor->tokens()->delete();
        $realtor->delete();

        return $this->success(null, 'Realtor deleted.');
    }

    public function billing(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        $realtor->load(['plan', 'suspendedPlan', 'subscriptions.payments', 'payments']);

        $subscriptions = Subscription::where('user_id', $realtor->id)->latest()->get();
        $payments = Payment::whereHas('subscription', fn ($q) => $q->where('user_id', $realtor->id))->latest()->get();
        $invoices = Invoice::where('user_id', $realtor->id)->latest()->get();

        return $this->success([
            'realtor' => new UserResource($realtor),
            'plans' => PlanResource::collection(Plan::ordered()->get()),
            'billing_plans' => config('billing.plans'),
            'subscriptions' => SubscriptionResource::collection($subscriptions),
            'payments' => PaymentResource::collection($payments),
            'invoices' => InvoiceResource::collection($invoices),
        ]);
    }

    public function assignPlan(StoreSubscriptionRequest $request, User $realtor, InvoiceService $invoices)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        DB::beginTransaction();

        try {
            $subscription = $realtor->subscriptions()->create([
                'plan_name' => $request->plan_name,
                'duration_months' => $request->duration_months,
                'start_date' => $request->start_date,
                'expiry_date' => $request->expiry_date,
                'price' => $request->price,
                'gst' => $request->gst ?? 0,
                'discount' => $request->discount ?? 0,
                'status' => $request->subscription_status,
                'internal_notes' => $request->internal_notes,
            ]);

            $total = (float) $request->price + (float) ($request->gst ?? 0) - (float) ($request->discount ?? 0);

            $payment = $subscription->payments()->create([
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'amount' => $total,
                'currency' => $request->currency,
                'payment_status' => $request->payment_status,
                'payment_date' => now(),
            ]);

            $invoice = $invoices->generateInvoice($subscription, $payment, [
                'price' => $request->price,
                'gst' => $request->gst ?? 0,
                'discount' => $request->discount ?? 0,
            ]);

            $plan = Plan::where('name', $request->plan_name)->first();
            if ($plan) {
                $realtor->update(['plan_id' => $plan->id]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error('Failed to assign plan. Please try again.', 500);
        }

        return $this->success([
            'subscription' => new SubscriptionResource($subscription->fresh()->load('user')),
            'payment' => new PaymentResource($payment->fresh()),
            'invoice' => new InvoiceResource($invoice->fresh()->load('invoiceItems')),
        ], 'Plan assigned and invoice generated.', 201);
    }

    public function suspend(Request $request, User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        if ($realtor->isSubscriptionSuspended()) {
            return $this->error('Subscription is already suspended.', 409);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $realtor->suspendSubscription($validated['reason'] ?? null);

        return $this->success(new RealtorDetailResource($realtor->fresh()->load(['plan', 'suspendedPlan'])), 'Subscription suspended.');
    }

    public function resume(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            return $this->error('Realtor not found.', 404);
        }

        if (!$realtor->isSubscriptionSuspended()) {
            return $this->error('Subscription is not suspended.', 409);
        }

        $realtor->resumeSubscription();

        return $this->success(new RealtorDetailResource($realtor->fresh()->load(['plan', 'suspendedPlan'])), 'Subscription resumed.');
    }
}
