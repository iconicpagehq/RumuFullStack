<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlansController extends ApiController
{
    public function index()
    {
        $plans = Plan::withCount('users')->ordered()->get();

        return $this->success(PlanResource::collection($plans));
    }

    public function store(Request $request, PlatformAuditService $audit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans,name',
            'key' => ['nullable', 'string', 'max:50', 'unique:plans,key'],
            'audience' => ['sometimes', Rule::in(['realtor', 'enterprise', 'both'])],
            'price' => 'nullable|numeric|min:0',
            'max_campaigns' => 'nullable|integer|min:0',
            'max_leads_per_campaign' => 'nullable|integer|min:0',
            'max_managers' => 'nullable|integer|min:0',
            'max_employees' => 'nullable|integer|min:0',
            'max_teams' => 'nullable|integer|min:0',
            'max_monthly_leads' => 'nullable|integer|min:0',
            'max_inventory_units' => 'nullable|integer|min:0',
            'advanced_reports' => 'sometimes|boolean',
            'meta_integrations' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'header_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $validated['audience'] = $validated['audience'] ?? 'realtor';
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['meta_integrations'] = $request->boolean('meta_integrations');

        $plan = Plan::create($validated);

        if ($plan->is_default) {
            Plan::where('id', '!=', $plan->id)
                ->where('audience', $plan->audience === 'enterprise' ? 'enterprise' : $plan->audience)
                ->update(['is_default' => false]);
        }

        $audit->log($request->user(), 'plan.created', $plan, 'Plan created', ['name' => $plan->name]);

        return $this->success(new PlanResource($plan), 'Plan created.', 201);
    }

    public function update(Request $request, Plan $plan, PlatformAuditService $audit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => ['nullable', 'string', 'max:50', Rule::unique('plans', 'key')->ignore($plan->id)],
            'audience' => ['sometimes', Rule::in(['realtor', 'enterprise', 'both'])],
            'price' => 'nullable|numeric|min:0',
            'max_campaigns' => 'nullable|integer|min:0',
            'max_leads_per_campaign' => 'nullable|integer|min:0',
            'max_managers' => 'nullable|integer|min:0',
            'max_employees' => 'nullable|integer|min:0',
            'max_teams' => 'nullable|integer|min:0',
            'max_monthly_leads' => 'nullable|integer|min:0',
            'max_inventory_units' => 'nullable|integer|min:0',
            'advanced_reports' => 'sometimes|boolean',
            'meta_integrations' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'header_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($plan->is_default) {
            $validated['is_active'] = true;
        } elseif ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('advanced_reports')) {
            $validated['advanced_reports'] = $request->boolean('advanced_reports');
        }

        if ($request->has('meta_integrations')) {
            $validated['meta_integrations'] = $request->boolean('meta_integrations');
        }

        $plan->update($validated);

        $audit->log($request->user(), 'plan.updated', $plan, 'Plan updated', ['name' => $plan->name]);

        return $this->success(new PlanResource($plan), 'Plan updated.');
    }

    public function reorder(Request $request, PlatformAuditService $audit)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:plans,id',
        ]);

        foreach ($validated['order'] as $position => $planId) {
            Plan::where('id', $planId)->update(['sort_order' => $position + 1]);
        }

        $audit->log($request->user(), 'plan.reordered', null, 'Plans reordered', ['order' => $validated['order']]);

        return $this->success(null, 'Plans reordered.');
    }

    public function toggleStatus(Request $request, Plan $plan, PlatformAuditService $audit)
    {
        if ($plan->is_default && $plan->is_active) {
            return $this->error('Cannot deactivate the default plan. Set another plan as default first.', 409);
        }

        $plan->update(['is_active' => !$plan->is_active]);

        $audit->log($request->user(), 'plan.status_toggled', $plan, 'Plan status updated', [
            'is_active' => $plan->is_active,
        ]);

        return $this->success(new PlanResource($plan), 'Plan status updated.');
    }
}

