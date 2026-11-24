<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\CallResource;
use App\Http\Resources\EnterpriseLeadResource;
use App\Models\EnterpriseMembership;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\SalesTarget;
use App\Models\SiteVisit;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Services\Calls\CallAccessService;
use App\Services\Calls\CallMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnterpriseDashboardController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $leads = $this->leadScope(Lead::where('enterprise_id', $enterprise->id), $request);
        $tasks = $this->taskScope(Task::where('enterprise_id', $enterprise->id), $request);
        $visits = $this->visitScope(SiteVisit::where('enterprise_id', $enterprise->id), $request);

        $stats = $this->stats($request, $leads, $tasks, $visits);

        $funnel = collect(Lead::ENTERPRISE_STAGES)->map(function ($stage) use ($leads) {
            return [
                'stage' => $stage,
                'label' => ucwords(str_replace('_', ' ', $stage)),
                'count' => (clone $leads)->where('enterprise_stage', $stage)->count(),
            ];
        });

        $sources = (clone $leads)
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['source' => $row->source ?: 'unknown', 'count' => (int) $row->count]);

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLeads = (clone $leads)->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month);
            $monthWon = (clone $leads)->where('enterprise_stage', 'won')
                ->whereYear('won_at', $month->year)->whereMonth('won_at', $month->month);

            $trend[] = [
                'month' => $month->format('M Y'),
                'leads' => (clone $monthLeads)->count(),
                'won' => (clone $monthWon)->count(),
                'deal_value' => (float) (clone $monthWon)->sum('deal_value'),
            ];
        }

        $recentLeads = (clone $leads)->with(['assignee', 'team', 'project'])
            ->latest()->limit(6)->get();

        $recentDeals = (clone $leads)->with(['assignee', 'team', 'project'])
            ->where('enterprise_stage', 'won')->latest('won_at')->limit(5)->get();

        $pendingAssignments = (clone $leads)->whereNull('assigned_to')
            ->whereNotIn('enterprise_stage', ['won', 'lost'])->count();

        $teamPerformance = $this->teamPerformance($request);
        $employeePerformance = $this->employeePerformance($request);
        $campaignPerformance = $this->campaignPerformance($request);

        $targets = $this->targetSummary($request);

        $activity = LeadActivity::where('enterprise_id', $enterprise->id)
            ->whereIn('lead_id', (clone $leads)->select('id'))
            ->with('user')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (LeadActivity $item) => [
                'id' => $item->id,
                'type' => $item->type,
                'title' => $item->title,
                'description' => $item->description,
                'user' => $item->user?->name,
                'created_at' => $item->created_at?->toIso8601String(),
            ]);

        $calls = app(CallMetricsService::class)->payload(
            app(CallAccessService::class)->enterpriseQuery($membership),
            5
        );
        $calls['recent'] = CallResource::collection($calls['recent'])->resolve();

        return $this->success([
            'role' => $membership->role,
            'stats' => $stats,
            'funnel' => $funnel,
            'sources' => $sources,
            'trend' => $trend,
            'recent_leads' => EnterpriseLeadResource::collection($recentLeads),
            'recent_deals' => EnterpriseLeadResource::collection($recentDeals),
            'pending_assignments' => $pendingAssignments,
            'team_performance' => $teamPerformance,
            'employee_performance' => $employeePerformance,
            'campaign_performance' => $campaignPerformance,
            'targets' => $targets,
            'tasks' => [
                'overdue' => (clone $tasks)->overdue()->count(),
                'today' => (clone $tasks)->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
                    ->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'pending' => (clone $tasks)->whereIn('status', ['pending', 'in_progress'])->count(),
                'completed_this_month' => (clone $tasks)->where('status', 'completed')
                    ->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'visits' => [
                'upcoming' => (clone $visits)->where('status', 'scheduled')->where('scheduled_at', '>=', now())->count(),
                'today' => (clone $visits)->whereDate('scheduled_at', now()->toDateString())->count(),
                'completed_this_month' => (clone $visits)->where('status', 'completed')
                    ->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'activity' => $activity,
            'calls' => $calls,
        ]);
    }

    private function stats(Request $request, $leads, $tasks, $visits): array
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $isLeadEmployee = $membership->isEmployee() && $this->hierarchyService()->hasReports($membership);

        $stats = [
            ['key' => 'total_leads', 'label' => $membership->isEmployee() && !$isLeadEmployee ? 'My leads' : ($membership->isEmployee() ? 'My team leads' : 'Total leads'), 'value' => (clone $leads)->count()],
            ['key' => 'new_leads', 'label' => 'New leads', 'value' => (clone $leads)->where('enterprise_stage', 'new')->count()],
            ['key' => 'qualified', 'label' => 'Qualified', 'value' => (clone $leads)->where('enterprise_stage', 'qualified')->count()],
            ['key' => 'negotiation', 'label' => 'In negotiation', 'value' => (clone $leads)->where('enterprise_stage', 'negotiation')->count()],
            ['key' => 'won', 'label' => 'Deals won', 'value' => (clone $leads)->where('enterprise_stage', 'won')->count()],
            ['key' => 'deal_value', 'label' => 'Won deal value', 'value' => (float) (clone $leads)->where('enterprise_stage', 'won')->sum('deal_value'), 'format' => 'currency'],
            ['key' => 'overdue_followups', 'label' => 'Overdue follow-ups', 'value' => (clone $leads)->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', now())->whereNotIn('enterprise_stage', ['won', 'lost'])->count()],
            ['key' => 'overdue_tasks', 'label' => 'Overdue tasks', 'value' => (clone $tasks)->overdue()->count()],
            ['key' => 'upcoming_visits', 'label' => 'Upcoming visits', 'value' => (clone $visits)->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())->count()],
        ];

        if ($membership->isEmployee()) {
            $stats[] = ['key' => 'lost', 'label' => 'Deals lost', 'value' => (clone $leads)->where('enterprise_stage', 'lost')->count()];
            $stats[] = ['key' => 'follow_up_today', 'label' => "Today's follow-ups", 'value' => (clone $leads)
                ->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()])->count()];

            return $stats;
        }

        $stats[] = ['key' => 'lost', 'label' => 'Deals lost', 'value' => (clone $leads)->where('enterprise_stage', 'lost')->count()];
        $stats[] = ['key' => 'unassigned', 'label' => 'Unassigned leads', 'value' => (clone $leads)->whereNull('assigned_to')
            ->whereNotIn('enterprise_stage', ['won', 'lost'])->count()];

        $stats[] = ['key' => 'active_teams', 'label' => 'Active teams', 'value' => Team::where('enterprise_id', $enterprise->id)
            ->where('status', 'active')
            ->when($membership->isManager(), fn ($q) => $q->where('manager_id', $membership->user_id))
            ->count()];
        $stats[] = ['key' => 'active_members', 'label' => 'Active team members', 'value' => EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('status', 'active')
            ->when(!$membership->isAdmin(), fn ($query) => $query->whereIn('id', $this->hierarchyService()->scopeIds($membership)))
            ->whereIn('role', ['manager', 'employee'])
            ->count()];
        $stats[] = ['key' => 'available_units', 'label' => 'Available units', 'value' => InventoryUnit::query()
            ->forEnterprise($enterprise->id)->where('availability_status', 'available')->count()];

        return $stats;
    }

    private function teamPerformance(Request $request): array
    {
        $membership = $this->membership($request);

        if ($membership->isEmployee()) {
            return [];
        }

        $teams = Team::where('enterprise_id', $membership->enterprise_id)
            ->with('manager')
            ->withCount('memberships')
            ->withCount(['leads as open_leads_count' => fn ($q) => $q->whereNotIn('enterprise_stage', ['won', 'lost'])])
            ->withCount(['leads as won_deals_count' => fn ($q) => $q->where('enterprise_stage', 'won')])
            ->withSum(['leads as deal_value' => fn ($q) => $q->where('enterprise_stage', 'won')], 'deal_value')
            ->when($membership->isManager(), fn ($query) => $query->where('manager_id', $membership->user_id))
            ->orderByDesc('deal_value')
            ->limit(8)
            ->get();

        return $teams->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'manager' => $team->manager?->name,
            'members' => (int) $team->memberships_count,
            'open_leads' => (int) $team->open_leads_count,
            'won_deals' => (int) $team->won_deals_count,
            'deal_value' => (float) ($team->deal_value ?? 0),
        ])->all();
    }

    private function employeePerformance(Request $request): array
    {
        $membership = $this->membership($request);

        if ($membership->isEmployee()) {
            return [];
        }

        $memberQuery = EnterpriseMembership::where('enterprise_id', $membership->enterprise_id)
            ->where('status', 'active')
            ->whereIn('role', ['manager', 'employee'])
            ->with('user');

        if ($membership->isManager()) {
            $memberQuery->whereIn('user_id', $this->hierarchyService()->scopeUserIds($membership));
        }

        $members = $memberQuery->get();

        $won = Lead::where('enterprise_id', $membership->enterprise_id)
            ->where('enterprise_stage', 'won')
            ->whereIn('assigned_to', $members->pluck('user_id'))
            ->select('assigned_to', DB::raw('COUNT(*) as won_count'), DB::raw('SUM(deal_value) as deal_value'))
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $open = Lead::where('enterprise_id', $membership->enterprise_id)
            ->whereNotIn('enterprise_stage', ['won', 'lost'])
            ->whereIn('assigned_to', $members->pluck('user_id'))
            ->select('assigned_to', DB::raw('COUNT(*) as open_count'))
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        return $members->map(function (EnterpriseMembership $member) use ($won, $open) {
            return [
                'user_id' => $member->user_id,
                'name' => $member->user?->name,
                'role' => $member->role,
                'open_leads' => (int) ($open[$member->user_id]->open_count ?? 0),
                'won_deals' => (int) ($won[$member->user_id]->won_count ?? 0),
                'deal_value' => (float) ($won[$member->user_id]->deal_value ?? 0),
            ];
        })->sortByDesc('deal_value')->take(8)->values()->all();
    }

    private function campaignPerformance(Request $request): array
    {
        $membership = $this->membership($request);

        if ($membership->isEmployee()) {
            return [];
        }

        return \App\Models\Campaign::where('enterprise_id', $membership->enterprise_id)
            ->withCount('leads')
            ->withCount(['leads as won_deals' => fn ($query) => $query->where('enterprise_stage', 'won')])
            ->withSum(['leads as deal_value' => fn ($query) => $query->where('enterprise_stage', 'won')], 'deal_value')
            ->when($membership->isManager(), fn ($query) => $query->whereIn('team_id', $this->teamScopeIds($request)))
            ->orderByDesc('leads_count')
            ->limit(6)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'leads' => (int) $campaign->leads_count,
                'won_deals' => (int) $campaign->won_deals,
                'deal_value' => (float) ($campaign->deal_value ?? 0),
            ])
            ->all();
    }

    private function targetSummary(Request $request): array
    {
        $membership = $this->membership($request);
        $month = now()->startOfMonth();

        $query = SalesTarget::where('enterprise_id', $membership->enterprise_id)
            ->whereBetween('period_month', [$month, $month->copy()->endOfMonth()])
            ->with(['team', 'user']);

        if (!$membership->isAdmin()) {
            $this->hierarchyService()->applyTargetScope($query, $membership);
        }

        return $query->get()->map(function (SalesTarget $target) {
            $actual = $this->targetActual($target);
            $percentage = (float) $target->target_value > 0
                ? round(($actual / (float) $target->target_value) * 100, 1)
                : null;

            return [
                'id' => $target->id,
                'metric' => $target->metric,
                'scope' => $target->user_id ? ($target->user?->name ?? 'Team member') : ($target->team?->name ?? 'Enterprise'),
                'target_value' => (float) $target->target_value,
                'actual_value' => $actual,
                'achievement_percentage' => $percentage,
            ];
        })->values()->all();
    }

    private function targetActual(SalesTarget $target): float
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
                ->where('type', 'status_changed')->where('meta->to', 'qualified')
                ->whereBetween('created_at', [$start, $end])->whereIn('lead_id', (clone $leads)->select('id'))->count(),
            'site_visits_completed' => (float) (clone $visits)->where('status', 'completed')->whereBetween('completed_at', [$start, $end])->count(),
            'deals_won' => (float) (clone $leads)->where('enterprise_stage', 'won')->whereBetween('won_at', [$start, $end])->count(),
            'deal_value' => (float) (clone $leads)->where('enterprise_stage', 'won')->whereBetween('won_at', [$start, $end])->sum('deal_value'),
            default => 0.0,
        };
    }

    private function leadScope($query, Request $request)
    {
        return $this->hierarchyService()->applyLeadScope($query, $this->membership($request));
    }

    private function taskScope($query, Request $request)
    {
        return $this->hierarchyService()->applyTaskScope($query, $this->membership($request));
    }

    private function visitScope($query, Request $request)
    {
        return $this->hierarchyService()->applyVisitScope($query, $this->membership($request));
    }
}
