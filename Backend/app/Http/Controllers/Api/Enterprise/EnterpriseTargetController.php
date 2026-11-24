<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseSalesTargetResource;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\SalesTarget;
use App\Models\SiteVisit;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\EnterpriseAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterpriseTargetController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);
        $month = $this->resolveMonth($request);

        $query = SalesTarget::where('enterprise_id', $membership->enterprise_id)
            ->with(['team', 'user'])
            ->whereBetween('period_month', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        if (!$membership->isAdmin()) {
            $this->hierarchyService()->applyTargetScope($query, $membership);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('team_id', $teamId);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $targets = $query->orderBy('metric')->get()
            ->each(function (SalesTarget $target) {
                $target->actual_value = $this->actual($target);
            });

        return $this->success([
            'month' => $month->format('Y-m'),
            'targets' => EnterpriseSalesTargetResource::collection($targets),
            'metrics' => SalesTarget::METRICS,
        ]);
    }

    public function store(Request $request, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'period_month' => 'required|date_format:Y-m',
            'metric' => 'required|in:' . implode(',', SalesTarget::METRICS),
            'target_value' => 'required|numeric|min:0',
            'team_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
        ]);

        if ($membership->isManager()) {
            $teamIds = $this->teamScopeIds($request);

            if (empty($validated['team_id']) && empty($validated['user_id'])) {
                throw ValidationException::withMessages(['team_id' => ['Managers must set a team or employee target.']]);
            }

            if (!empty($validated['team_id']) && !in_array((int) $validated['team_id'], $teamIds, true)) {
                throw ValidationException::withMessages(['team_id' => ['Managers can only set targets for their own team.']]);
            }

            if (!empty($validated['user_id'])
                && !in_array((int) $validated['user_id'], $this->hierarchyService()->scopeUserIds($membership), true)) {
                throw ValidationException::withMessages(['user_id' => ['Managers can only set targets within their reporting line or team.']]);
            }
        }

        if (!empty($validated['team_id'])) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
        }

        if (!empty($validated['user_id'])) {
            $member = $enterprise->memberships()
                ->where('user_id', $validated['user_id'])
                ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                ->exists();
            if (!$member) {
                throw ValidationException::withMessages(['user_id' => ['Select an active enterprise member.']]);
            }
        }

        $period = \Carbon\Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth();

        $duplicate = SalesTarget::where('enterprise_id', $enterprise->id)
            ->where('metric', $validated['metric'])
            ->whereDate('period_month', $period->toDateString())
            ->when(
                !empty($validated['team_id']),
                fn ($query) => $query->where('team_id', $validated['team_id']),
                fn ($query) => $query->whereNull('team_id')
            )
            ->when(
                !empty($validated['user_id']),
                fn ($query) => $query->where('user_id', $validated['user_id']),
                fn ($query) => $query->whereNull('user_id')
            )
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['metric' => ['A target for this metric and scope already exists for the month.']]);
        }

        $target = SalesTarget::create([
            'enterprise_id' => $enterprise->id,
            'team_id' => $validated['team_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'period_month' => $period,
            'metric' => $validated['metric'],
            'target_value' => $validated['target_value'],
            'created_by' => $request->user()->id,
        ]);

        $audit->log($enterprise, $request->user(), 'target.created', $target, 'Sales target created', [
            'metric' => $target->metric,
            'target_value' => $target->target_value,
        ]);

        $target->actual_value = $this->actual($target);

        return $this->success(
            new EnterpriseSalesTargetResource($target->load(['team', 'user'])),
            'Sales target created.',
            201
        );
    }

    public function update(Request $request, SalesTarget $target, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $this->ensureTargetVisible($request, $target);

        $validated = $request->validate([
            'target_value' => 'required|numeric|min:0',
        ]);

        $target->update(['target_value' => $validated['target_value']]);

        $audit->log($this->enterprise($request), $request->user(), 'target.updated', $target);

        $target->actual_value = $this->actual($target);

        return $this->success(new EnterpriseSalesTargetResource($target->load(['team', 'user'])), 'Sales target updated.');
    }

    public function destroy(Request $request, SalesTarget $target, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $this->ensureTargetVisible($request, $target);

        $target->delete();
        $audit->log($this->enterprise($request), $request->user(), 'target.deleted', $target);

        return $this->success(null, 'Sales target deleted.');
    }

    private function ensureTargetVisible(Request $request, SalesTarget $target): void
    {
        $membership = $this->membership($request);
        abort_if($target->enterprise_id !== $membership->enterprise_id, 404);

        if ($membership->isEmployee()) {
            abort(403, 'You cannot manage targets.');
        }

        abort_if(
            !$this->hierarchyService()->targetVisible($target, $membership),
            403,
            'This target is outside your scope.'
        );
    }

    private function resolveMonth(Request $request): \Carbon\Carbon
    {
        $month = $request->input('month');

        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return now()->startOfMonth();
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }

    private function actual(SalesTarget $target): float
    {
        $start = $target->period_month->copy()->startOfMonth();
        $end = $target->period_month->copy()->endOfMonth();

        $leads = Lead::where('enterprise_id', $target->enterprise_id);
        $visits = SiteVisit::where('enterprise_id', $target->enterprise_id);

        if ($target->user_id) {
            $leads->where('assigned_to', $target->user_id);
            $visits->where('assigned_to', $target->user_id);
        } elseif ($target->team_id) {
            $leads->where('team_id', $target->team_id);
            $visits->where('team_id', $target->team_id);
        }

        return match ($target->metric) {
            'leads_assigned' => (float) (clone $leads)->whereBetween('assigned_at', [$start, $end])->count(),
            'leads_contacted' => (float) (clone $leads)->whereBetween('last_contacted_at', [$start, $end])->count(),
            'leads_qualified' => (float) LeadActivity::where('enterprise_id', $target->enterprise_id)
                ->where('type', 'status_changed')
                ->where('meta->to', 'qualified')
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('lead_id', (clone $leads)->select('id'))
                ->count(),
            'site_visits_completed' => (float) (clone $visits)->where('status', 'completed')
                ->whereBetween('completed_at', [$start, $end])->count(),
            'deals_won' => (float) (clone $leads)->where('enterprise_stage', 'won')
                ->whereBetween('won_at', [$start, $end])->count(),
            'deal_value' => (float) (clone $leads)->where('enterprise_stage', 'won')
                ->whereBetween('won_at', [$start, $end])->sum('deal_value'),
            'revenue_received' => 0.0,
            default => 0.0,
        };
    }
}
