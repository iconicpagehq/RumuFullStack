<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PlanResource;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\EnterpriseLimitService;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;

class EnterpriseController extends ApiController
{
    public function index(Request $request)
    {
        $query = Enterprise::with(['owner:id,name,email', 'plan'])
            ->withCount([
                'memberships as active_members_count' => fn ($membership) => $membership->where('status', EnterpriseMembership::STATUS_ACTIVE),
                'memberships as manager_count' => fn ($membership) => $membership->where('status', EnterpriseMembership::STATUS_ACTIVE)->where('role', 'manager'),
                'memberships as employee_count' => fn ($membership) => $membership->where('status', EnterpriseMembership::STATUS_ACTIVE)->where('role', 'employee'),
                'teams',
                'leads',
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        $paginator = $query->latest('id')->paginate((int) $request->input('per_page', 20));

        $paginator->getCollection()->transform(fn (Enterprise $enterprise) => [
            'id' => $enterprise->id,
            'name' => $enterprise->name,
            'slug' => $enterprise->slug,
            'email' => $enterprise->email,
            'phone' => $enterprise->phone,
            'status' => $enterprise->status,
            'logo_url' => $enterprise->logoUrl(),
            'owner' => $enterprise->owner ? [
                'id' => $enterprise->owner->id,
                'name' => $enterprise->owner->name,
                'email' => $enterprise->owner->email,
            ] : null,
            'plan' => $enterprise->plan ? [
                'id' => $enterprise->plan->id,
                'name' => $enterprise->plan->name,
            ] : null,
            'active_members' => $enterprise->active_members_count,
            'managers' => $enterprise->manager_count,
            'employees' => $enterprise->employee_count,
            'teams' => $enterprise->teams_count,
            'leads' => $enterprise->leads_count,
            'leads_this_month' => $enterprise->leads()->where('created_at', '>=', now()->startOfMonth())->count(),
            'created_at' => $enterprise->created_at?->toIso8601String(),
        ]);

        return $this->paginated($paginator);
    }

    public function show(Enterprise $enterprise, EnterpriseLimitService $limits)
    {
        $enterprise->load(['owner', 'plan']);

        $members = $enterprise->memberships()
            ->notRemoved()
            ->with(['user:id,name,email,phone', 'primaryTeamMembership.team:id,name'])
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")
            ->get()
            ->map(fn (EnterpriseMembership $membership) => [
                'id' => $membership->id,
                'user_id' => $membership->user_id,
                'name' => $membership->user?->name,
                'email' => $membership->user?->email,
                'phone' => $membership->user?->phone,
                'role' => $membership->role,
                'status' => $membership->status,
                'designation' => $membership->designation,
                'team' => $membership->primaryTeamMembership?->team?->name,
                'joined_at' => $membership->joined_at?->toIso8601String(),
            ]);

        $ownerId = $enterprise->owner_id;
        $subscription = $ownerId ? Subscription::where('user_id', $ownerId)->latest()->first() : null;
        $invoices = $ownerId
            ? Invoice::where('user_id', $ownerId)->latest()->limit(10)->get()->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'created_at' => $invoice->created_at?->toIso8601String(),
            ])
            : collect();

        $recentLeads = $enterprise->leads()
            ->with(['assignee:id,name', 'team:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'enterprise_stage' => $lead->enterprise_stage,
                'team' => $lead->team?->name,
                'assigned_to' => $lead->assignee?->name,
                'created_at' => $lead->created_at?->toIso8601String(),
            ]);

        $recentCampaigns = $enterprise->campaigns()
            ->withCount('leads')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'leads_count' => $campaign->leads_count,
                'created_at' => $campaign->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'enterprise' => [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'legal_name' => $enterprise->legal_name,
                'slug' => $enterprise->slug,
                'email' => $enterprise->email,
                'phone' => $enterprise->phone,
                'address' => $enterprise->address,
                'website' => $enterprise->website,
                'business_type' => $enterprise->business_type,
                'company_size' => $enterprise->company_size,
                'status' => $enterprise->status,
                'logo_url' => $enterprise->logoUrl(),
                'rera_number' => $enterprise->rera_number,
                'trial_ends_at' => $enterprise->trial_ends_at?->toIso8601String(),
                'is_on_trial' => $enterprise->isOnTrial(),
                'created_at' => $enterprise->created_at?->toIso8601String(),
                'owner' => $enterprise->owner ? [
                    'id' => $enterprise->owner->id,
                    'name' => $enterprise->owner->name,
                    'email' => $enterprise->owner->email,
                    'phone' => $enterprise->owner->phone,
                ] : null,
            ],
            'plan' => $enterprise->plan ? new PlanResource($enterprise->plan) : null,
            'usage' => $limits->usageSummary($enterprise),
            'counts' => [
                'members' => $enterprise->memberships()->where('status', EnterpriseMembership::STATUS_ACTIVE)->count(),
                'admins' => $enterprise->memberships()->where('status', EnterpriseMembership::STATUS_ACTIVE)->where('role', 'admin')->count(),
                'managers' => $enterprise->memberships()->where('status', EnterpriseMembership::STATUS_ACTIVE)->where('role', 'manager')->count(),
                'employees' => $enterprise->memberships()->where('status', EnterpriseMembership::STATUS_ACTIVE)->where('role', 'employee')->count(),
                'teams' => $enterprise->teams()->count(),
                'leads' => $enterprise->leads()->count(),
                'leads_this_month' => $enterprise->leads()->where('created_at', '>=', now()->startOfMonth())->count(),
                'campaigns' => $enterprise->campaigns()->count(),
                'audit_logs' => $enterprise->auditLogs()->count(),
            ],
            'members' => $members,
            'recent_leads' => $recentLeads,
            'recent_campaigns' => $recentCampaigns,
            'subscription' => $subscription ? [
                'plan_name' => $subscription->plan_name,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date?->toIso8601String(),
                'expiry_date' => $subscription->expiry_date?->toIso8601String(),
                'price' => $subscription->price,
            ] : null,
            'invoices' => $invoices,
        ]);
    }

    public function suspend(Request $request, Enterprise $enterprise, PlatformAuditService $audit)
    {
        if ($enterprise->status === 'suspended') {
            return $this->error('This enterprise is already suspended.', 409);
        }

        $enterprise->update(['status' => 'suspended']);

        $audit->log($request->user(), 'enterprise.suspended', $enterprise, 'Enterprise account suspended', [
            'enterprise_id' => $enterprise->id,
        ]);

        return $this->success(['status' => $enterprise->status], 'Enterprise suspended.');
    }

    public function resume(Request $request, Enterprise $enterprise, PlatformAuditService $audit)
    {
        if ($enterprise->status === 'active') {
            return $this->error('This enterprise is already active.', 409);
        }

        $enterprise->update(['status' => 'active']);

        $audit->log($request->user(), 'enterprise.resumed', $enterprise, 'Enterprise account resumed', [
            'enterprise_id' => $enterprise->id,
        ]);

        return $this->success(['status' => $enterprise->status], 'Enterprise resumed.');
    }
}
