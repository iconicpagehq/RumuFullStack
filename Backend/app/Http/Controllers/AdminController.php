<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Notification;
use App\Models\PlanUpgradeRequest;
use App\Support\MetaCapiSettings;
use App\Services\MetaCapiService;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        // Leads by status for pie chart
        $leadsByStatus = Lead::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $campaignsByStatus = Campaign::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $realtorsByPlan = User::where('role', 'realtor')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();
        
        // Get plan names for labels
        $planNames = Plan::pluck('name', 'id')->toArray();
        
        // Monthly activity for bar chart (last 6 months)
        $months = [];
        $leadsMonthly = [];
        $campaignsMonthly = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabel = $date->format('M Y');
            $months[] = $monthLabel;

            $leadsMonthly[] = Lead::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $campaignsMonthly[] = Campaign::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }
        
        return view('admin.dashboard', compact(
            'leadsByStatus',
            'campaignsByStatus',
            'realtorsByPlan',
            'planNames',
            'months',
            'leadsMonthly',
            'campaignsMonthly'
        ));
    }

    /**
     * Show all leads (admin view)
     */
    public function leads()
    {
        $leads = Lead::with('campaign')->latest()->paginate(15);
        return view('admin.leads.index', compact('leads'));
    }

    /**
     * Show all users
     */
    public function users()
    {
        $users = User::with('plan')->latest()->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        $metaCapi = app(MetaCapiService::class);

        return view('admin.settings.index', [
            'metaCapiEnabled' => MetaCapiSettings::isEnabled(),
            'metaCapiConfigured' => $metaCapi->isConfigured(),
        ]);
    }

    /**
     * Show all plans
     */
    public function plans()
    {
        $plans = Plan::withCount('users')->ordered()->get();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show create plan form
     */
    public function createPlan()
    {
        return view('admin.plans.create');
    }

    /**
     * Store a new plan
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans,name',
            'price' => 'nullable|numeric|min:0',
            'max_campaigns' => 'nullable|integer|min:0',
            'max_leads_per_campaign' => 'nullable|integer|min:0',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'header_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        $plan = Plan::create($validated);

        if ($plan->is_default) {
            Plan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        return redirect()->route('admin.plans.index')->with('success', 'Plan created successfully!');
    }

    /**
     * Reorder plans (drag-and-drop).
     */
    public function reorderPlans(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:plans,id',
        ]);

        foreach ($validated['order'] as $position => $planId) {
            Plan::where('id', $planId)->update(['sort_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show edit plan form
     */
    public function editPlan(\App\Models\Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update plan
     */
    public function updatePlan(Request $request, \App\Models\Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'max_campaigns' => 'nullable|integer|min:0',
            'max_leads_per_campaign' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'header_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($plan->is_default) {
            $validated['is_active'] = true;
        } elseif ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $plan->update($validated);

        if ($plan->is_default && !$plan->is_active) {
            return redirect()->route('admin.plans.index')
                ->with('warning', 'Plan updated, but the default plan is inactive. Set another active default plan.');
        }

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated successfully!');
    }

    /**
     * Toggle plan active / inactive status
     */
    public function togglePlanStatus(Plan $plan)
    {
        if ($plan->is_default && $plan->is_active) {
            return back()->with('error', 'Cannot deactivate the default plan. Set another plan as default first, or edit the plan.');
        }

        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Plan \"{$plan->name}\" has been {$status}.");
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        // TODO: Implement settings update logic
        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully!');
    }

    /**
     * Update appearance settings (theme)
     */
    public function updateAppearance(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark,auto',
        ]);

        auth()->user()->update([
            'theme' => $request->theme,
        ]);

        return redirect()->route('admin.settings.index')->with('success', 'Theme updated successfully!');
    }

    public function updateMetaCapi(Request $request)
    {
        $request->validate([
            'meta_capi_enabled' => 'sometimes|boolean',
        ]);

        MetaCapiSettings::setEnabled($request->boolean('meta_capi_enabled'));

        return redirect()->route('admin.settings.index')->with('success', 'Meta Conversions API setting saved.');
    }

    /**
     * Show plan upgrade requests
     */
    public function upgradeRequests()
    {
        $requests = PlanUpgradeRequest::with(['user', 'currentPlan', 'requestedPlan', 'approvedBy'])
            ->latest()
            ->paginate(15);
            
        $pendingCount = PlanUpgradeRequest::where('status', 'pending')->count();
        
        return view('admin.upgrade_requests.index', compact('requests', 'pendingCount'));
    }

    /**
     * Approve plan upgrade request
     */
    public function approveUpgrade(Request $request, PlanUpgradeRequest $upgradeRequest)
    {
        // Validate that request is still pending
        if ($upgradeRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        // Update user's plan
        $user = $upgradeRequest->user;
        $user->plan_id = $upgradeRequest->requested_plan_id;
        $user->save();

        $requestedPlan = $upgradeRequest->requestedPlan;

        $upgradeRequest->update([
            'status' => 'approved',
            'admin_notes' => $request->notes,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'expires_at' => $requestedPlan?->isPro() ? now()->addDays(30) : null,
        ]);

        $planName = $requestedPlan?->name ?? 'selected';
        $expiryNote = $requestedPlan?->isPro() ? ' Valid for 30 days.' : '';

        return redirect()->route('admin.upgrade_requests.index')
            ->with('success', "{$user->name}'s plan has been upgraded to {$planName} successfully!{$expiryNote}");
    }

    /**
     * Reject plan upgrade request
     */
    public function rejectUpgrade(Request $request, PlanUpgradeRequest $upgradeRequest)
    {
        // Validate that request is still pending
        if ($upgradeRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $upgradeRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->reason,
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('admin.upgrade_requests.index')
            ->with('success', 'Upgrade request has been rejected.');
    }

    /**
     * Show all notifications
     */
    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);
        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Mark a notification as read
     */
    public function markNotificationRead(Notification $notification)
    {
        // Ensure the notification belongs to the current user
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        
        $notification->markAsRead();
        return redirect()->back();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead()
    {
        auth()->user()->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return redirect()->back();
    }
}
