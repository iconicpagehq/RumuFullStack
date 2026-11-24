<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\PlanResource;
use App\Models\EnterpriseSeatRequest;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnterpriseBillingController extends EnterpriseApiController
{
    public function index(Request $request, EnterpriseLimitService $limits)
    {
        $enterprise = $this->enterprise($request)->load('plan');
        $ownerId = $enterprise->owner_id ?? $request->user()->id;

        $subscription = Subscription::where('user_id', $ownerId)->latest()->first();

        $invoices = Invoice::where('user_id', $ownerId)
            ->latest()
            ->limit(24)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'created_at' => $invoice->created_at?->toIso8601String(),
            ]);

        $pendingUpgrade = PlanUpgradeRequest::where('user_id', $ownerId)
            ->where('status', 'pending')
            ->with('requestedPlan')
            ->first();

        $seatRequests = EnterpriseSeatRequest::where('enterprise_id', $enterprise->id)
            ->with(['requester', 'approver'])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (EnterpriseSeatRequest $seatRequest) => [
                'id' => $seatRequest->id,
                'seat_type' => $seatRequest->seat_type,
                'seat_label' => $seatRequest->seatLabel(),
                'quantity' => $seatRequest->quantity,
                'status' => $seatRequest->status,
                'note' => $seatRequest->note,
                'admin_notes' => $seatRequest->admin_notes,
                'requested_by' => $seatRequest->requester?->name,
                'approved_at' => $seatRequest->approved_at?->toIso8601String(),
                'created_at' => $seatRequest->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'enterprise' => $enterprise->name,
            'plan' => $enterprise->plan ? new PlanResource($enterprise->plan) : null,
            'trial_ends_at' => $enterprise->trial_ends_at?->toIso8601String(),
            'is_on_trial' => $enterprise->isOnTrial(),
            'usage' => $limits->usageSummary($enterprise),
            'extra_managers' => (int) ($enterprise->extra_managers ?? 0),
            'extra_employees' => (int) ($enterprise->extra_employees ?? 0),
            'seat_requests' => $seatRequests,
            'features' => [
                'advanced_reports' => $limits->feature($enterprise, 'advanced_reports'),
                'meta_integrations' => $limits->feature($enterprise, 'meta_integrations'),
            ],
            'subscription' => $subscription ? [
                'plan_name' => $subscription->plan_name,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date?->toIso8601String(),
                'expiry_date' => $subscription->expiry_date?->toIso8601String(),
                'price' => $subscription->price,
            ] : null,
            'invoices' => $invoices,
            'pending_upgrade' => $pendingUpgrade ? [
                'id' => $pendingUpgrade->id,
                'requested_plan' => new PlanResource($pendingUpgrade->requestedPlan),
                'created_at' => $pendingUpgrade->created_at?->toIso8601String(),
            ] : null,
            'available_plans' => PlanResource::collection(Plan::forEnterprise()->active()->ordered()->get()),
        ]);
    }

    public function requestUpgrade(
        Request $request,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $plan = Plan::forEnterprise()->active()->find($validated['plan_id']);
        if (!$plan) {
            throw ValidationException::withMessages(['plan_id' => ['Select an active enterprise plan.']]);
        }

        if ((int) $plan->id === (int) $enterprise->plan_id) {
            throw ValidationException::withMessages(['plan_id' => ['This is already your current plan.']]);
        }

        if ($enterprise->plan && ($plan->sort_order ?? 0) <= ($enterprise->plan->sort_order ?? 0)) {
            throw ValidationException::withMessages(['plan_id' => ['Select a higher enterprise plan.']]);
        }

        $ownerId = $enterprise->owner_id ?? $request->user()->id;

        $existing = PlanUpgradeRequest::where('user_id', $ownerId)->where('status', 'pending')->exists();
        if ($existing) {
            return $this->error('An upgrade request is already pending for this enterprise.', 409);
        }

        $upgrade = PlanUpgradeRequest::create([
            'user_id' => $ownerId,
            'current_plan_id' => $enterprise->plan_id ?? Plan::forEnterprise()->active()->value('id'),
            'requested_plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        $audit->log($enterprise, $request->user(), 'billing.upgrade_requested', $upgrade, 'Enterprise plan upgrade requested', [
            'requested_plan_id' => $plan->id,
        ]);

        return $this->success([
            'request_id' => $upgrade->id,
            'requested_plan' => new PlanResource($plan),
        ], 'Upgrade request sent to the Rumu platform team.', 201);
    }

    public function downloadInvoice(
        Request $request,
        Invoice $invoice,
        InvoicePdfService $pdfService,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);
        $ownerId = $enterprise->owner_id ?? $request->user()->id;

        abort_if((int) $invoice->user_id !== (int) $ownerId, 404);

        if (!$invoice->pdf_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($invoice->pdf_path)) {
            $invoice->update(['pdf_path' => $pdfService->generateAndStorePdf($invoice)]);
            $invoice->refresh();
        }

        $response = $pdfService->downloadPdf($invoice);

        if (!$response) {
            return $this->error('Invoice PDF is not available. Please try again later.', 404);
        }

        return $response;
    }

    public function requestSeatAddon(
        Request $request,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'seat_type' => 'required|in:' . implode(',', EnterpriseSeatRequest::SEAT_TYPES),
            'quantity' => 'required|integer|min:1|max:50',
            'note' => 'nullable|string|max:1000',
        ]);

        $resource = $validated['seat_type'];
        $limit = $limits->limit($enterprise, $resource);

        if ($limit === null) {
            throw ValidationException::withMessages([
                'seat_type' => ['Your plan already includes unlimited seats for this role.'],
            ]);
        }

        $hasPending = EnterpriseSeatRequest::where('enterprise_id', $enterprise->id)
            ->where('seat_type', $resource)
            ->where('status', EnterpriseSeatRequest::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return $this->error('A seat request for this role is already pending approval.', 409);
        }

        $seatRequest = EnterpriseSeatRequest::create([
            'enterprise_id' => $enterprise->id,
            'seat_type' => $resource,
            'quantity' => $validated['quantity'],
            'status' => EnterpriseSeatRequest::STATUS_PENDING,
            'note' => $validated['note'] ?? null,
            'requested_by' => $request->user()->id,
        ]);

        $audit->log($enterprise, $request->user(), 'billing.seat_requested', $seatRequest, 'Seat add-on requested', [
            'seat_type' => $resource,
            'quantity' => $seatRequest->quantity,
        ]);

        return $this->success([
            'request' => [
                'id' => $seatRequest->id,
                'seat_type' => $seatRequest->seat_type,
                'seat_label' => $seatRequest->seatLabel(),
                'quantity' => $seatRequest->quantity,
                'status' => $seatRequest->status,
                'note' => $seatRequest->note,
                'created_at' => $seatRequest->created_at?->toIso8601String(),
            ],
        ], 'Seat add-on request sent to the Rumu platform team.', 201);
    }
}
