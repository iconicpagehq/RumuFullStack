<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Models\Campaign;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\SalesTarget;
use App\Models\SiteVisit;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Services\EnterpriseLimitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnterpriseReportController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        [$from, $to, $groupBy] = $this->period($request);
        $leads = $this->reportLeadScope($request, $from, $to);
        $visits = $this->reportVisitScope($request, $from, $to);
        $tasks = $this->reportTaskScope($request, $from, $to);

        $totalLeads = (clone $leads)->count();
        $contacted = (clone $leads)->whereIn('enterprise_stage', Lead::ENTERPRISE_STAGES)
            ->whereNotNull('last_contacted_at')->count();
        $qualified = (clone $leads)->whereIn('enterprise_stage', ['qualified', 'site_visit_scheduled', 'site_visit_completed', 'negotiation', 'won'])
            ->count();
        $won = (clone $leads)->where('enterprise_stage', 'won')->count();
        $lost = (clone $leads)->where('enterprise_stage', 'lost')->count();
        $decided = $won + $lost;
        $dealValue = (float) (clone $leads)->where('enterprise_stage', 'won')->sum('deal_value');
        $visitsCompleted = (clone $visits)->where('status', 'completed')->count();
        $tasksCompleted = (clone $tasks)->where('status', 'completed')->count();

        $summary = [
            'total_leads' => $totalLeads,
            'contacted' => $contacted,
            'qualified' => $qualified,
            'won' => $won,
            'lost' => $lost,
            'win_rate' => $decided > 0 ? round(($won / $decided) * 100, 1) : null,
            'deal_value' => $dealValue,
            'average_deal_value' => $won > 0 ? round($dealValue / $won, 2) : 0,
            'site_visits' => (clone $visits)->count(),
            'site_visits_completed' => $visitsCompleted,
            'tasks_completed' => $tasksCompleted,
            'activities' => LeadActivity::where('enterprise_id', $this->enterprise($request)->id)
                ->whereBetween('created_at', [$from, $to])
                ->whereIn('lead_id', (clone $leads)->select('id'))
                ->count(),
        ];

        $stages = collect(Lead::ENTERPRISE_STAGES)->map(fn ($stage) => [
            'stage' => $stage,
            'label' => ucwords(str_replace('_', ' ', $stage)),
            'count' => (clone $leads)->where('enterprise_stage', $stage)->count(),
        ]);

        $sources = (clone $leads)
            ->select('source', DB::raw('COUNT(*) as count'), DB::raw('SUM(CASE WHEN enterprise_stage = \'won\' THEN 1 ELSE 0 END) as won'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source ?: 'unknown',
                'count' => (int) $row->count,
                'won' => (int) $row->won,
            ]);

        $priorities = (clone $leads)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get()
            ->map(fn ($row) => ['priority' => $row->priority ?: 'medium', 'count' => (int) $row->count]);

        return $this->success([
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'group_by' => $groupBy,
            ],
            'filters' => $this->activeFilters($request),
            'summary' => $summary,
            'stages' => $stages,
            'sources' => $sources,
            'priorities' => $priorities,
            'trend' => $this->trend($request, $from, $to, $groupBy),
            'team_performance' => $this->teamPerformance($request, $from, $to),
            'employee_performance' => $this->employeePerformance($request, $from, $to),
            'campaign_performance' => $this->campaignPerformance($request, $from, $to),
            'activities' => $this->activityBreakdown($request, $from, $to, $leads),
            'visits' => [
                'scheduled' => (clone $visits)->where('status', 'scheduled')->count(),
                'completed' => $visitsCompleted,
                'cancelled' => (clone $visits)->where('status', 'cancelled')->count(),
                'no_show' => (clone $visits)->where('status', 'no_show')->count(),
            ],
            'tasks' => [
                'total' => (clone $tasks)->count(),
                'completed' => $tasksCompleted,
                'overdue' => (clone $tasks)->overdue()->count(),
                'pending' => (clone $tasks)->whereIn('status', ['pending', 'in_progress'])->count(),
            ],
        ]);
    }

    public function targets(Request $request)
    {
        [$from, $to] = $this->period($request);
        $membership = $this->membership($request);

        $query = $this->hierarchyService()->applyTargetScope(SalesTarget::query(), $membership)
            ->whereBetween('period_month', [$from->copy()->startOfMonth(), $to->copy()->endOfMonth()])
            ->with(['team', 'user']);

        return $this->success($query->get()->map(fn (SalesTarget $target) => [
            'id' => $target->id,
            'period_month' => $target->period_month->toDateString(),
            'metric' => $target->metric,
            'scope' => $target->user_id ? ($target->user?->name ?? 'Team member') : ($target->team?->name ?? 'Enterprise'),
            'target_value' => (float) $target->target_value,
            'actual_value' => $this->targetActual($target),
        ])->values());
    }

    public function export(Request $request, EnterpriseLimitService $limits)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $limits->requireFeature($this->enterprise($request), 'advanced_reports');

        $validated = $request->validate([
            'type' => 'required|in:leads,deals,visits,tasks',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'team_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'stage' => 'nullable|string|max:50',
        ]);

        [$from, $to] = $this->period($request);
        $type = $validated['type'];
        $filename = "enterprise_{$type}_report_{$from->format('Ymd')}_{$to->format('Ymd')}.csv";

        $headers = $this->exportHeaders($type);
        $rows = $this->exportRows($request, $type, $from, $to);

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => $this->csvSafe($value), $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function exportHeaders(string $type): array
    {
        return match ($type) {
            'leads', 'deals' => ['Lead ID', 'Name', 'Phone', 'Email', 'Stage', 'Priority', 'Source', 'Team', 'Assigned To', 'Budget Min', 'Budget Max', 'Deal Value', 'Lost Reason', 'Created At', 'Won At'],
            'visits' => ['Visit ID', 'Lead', 'Project', 'Unit', 'Assigned To', 'Scheduled At', 'Status', 'Outcome', 'Feedback', 'Customer Attended', 'Completed At'],
            'tasks' => ['Task ID', 'Title', 'Lead', 'Assigned To', 'Priority', 'Status', 'Due At', 'Completed At'],
        };
    }

    private function exportRows(Request $request, string $type, Carbon $from, Carbon $to): array
    {
        if ($type === 'leads' || $type === 'deals') {
            $query = $this->reportLeadScope($request, $from, $to)
                ->with(['team', 'assignee'])
                ->when($type === 'deals', fn ($q) => $q->whereIn('enterprise_stage', ['won', 'lost']))
                ->when($request->filled('stage'), fn ($q) => $q->where('enterprise_stage', $request->string('stage')))
                ->latest();

            return $query->cursor()->map(fn (Lead $lead) => [
                $lead->id,
                $lead->name,
                $lead->phone,
                $lead->email,
                $lead->enterprise_stage,
                $lead->priority,
                $lead->source,
                $lead->team?->name,
                $lead->assignee?->name,
                $lead->budget_min,
                $lead->budget_max,
                $lead->deal_value,
                $lead->lost_reason,
                $lead->created_at?->toDateTimeString(),
                $lead->won_at?->toDateTimeString(),
            ])->all();
        }

        if ($type === 'visits') {
            return $this->reportVisitScope($request, $from, $to)
                ->with(['lead', 'project', 'unit', 'assignee'])
                ->latest('scheduled_at')
                ->cursor()
                ->map(fn (SiteVisit $visit) => [
                    $visit->id,
                    $visit->lead?->name,
                    $visit->project?->name,
                    $visit->unit?->unit_number,
                    $visit->assignee?->name,
                    $visit->scheduled_at?->toDateTimeString(),
                    $visit->status,
                    $visit->outcome,
                    $visit->feedback,
                    $visit->customer_attended ? 'yes' : 'no',
                    $visit->completed_at?->toDateTimeString(),
                ])->all();
        }

        return $this->reportTaskScope($request, $from, $to)
            ->with(['lead', 'assignee'])
            ->latest()
            ->cursor()
            ->map(fn (Task $task) => [
                $task->id,
                $task->title,
                $task->lead?->name,
                $task->assignee?->name,
                $task->priority,
                $task->status,
                $task->due_at?->toDateTimeString(),
                $task->completed_at?->toDateTimeString(),
            ])->all();
    }

    private function csvSafe(mixed $value): string
    {
        $string = $value === null ? '' : (string) $value;

        if ($string !== '' && preg_match('/^[=+\-@\t\r]/', $string)) {
            return "'" . $string;
        }

        return $string;
    }

    private function period(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->subDays(29)->startOfDay();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        if ($from->diffInDays($to) > 366) {
            abort(422, 'Report periods cannot exceed 366 days.');
        }

        $groupBy = $validated['group_by'] ?? ($from->diffInDays($to) <= 31 ? 'day' : 'month');

        return [$from, $to, $groupBy];
    }

    private function activeFilters(Request $request): array
    {
        return [
            'team_id' => $request->integer('team_id') ?: null,
            'user_id' => $request->integer('user_id') ?: null,
        ];
    }

    private function filters(Request $request, $query, string $teamColumn = 'team_id', string $userColumn = 'assigned_to')
    {
        $membership = $this->membership($request);

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $allowed = $membership->isAdmin()
                || in_array($teamId, $this->teamScopeIds($request), true);

            if (!$allowed) {
                abort(403, 'You cannot report on that team.');
            }

            $query->where($teamColumn, $teamId);
        }

        if ($request->filled('user_id')) {
            $userId = (int) $request->input('user_id');
            $this->assertUserInScope($request, $userId);
            $query->where($userColumn, $userId);
        }

        return $query;
    }

    private function assertUserInScope(Request $request, int $userId): void
    {
        $membership = $this->membership($request);

        if ($membership->isAdmin()) {
            return;
        }

        if (!in_array($userId, $this->hierarchyService()->scopeUserIds($membership), true)) {
            abort(403, 'You cannot report on that user.');
        }
    }

    private function reportLeadScope(Request $request, Carbon $from, Carbon $to)
    {
        $query = $this->hierarchyService()
            ->applyLeadScope(Lead::query(), $this->membership($request))
            ->whereBetween('created_at', [$from, $to]);

        return $this->filters($request, $query);
    }

    private function reportVisitScope(Request $request, Carbon $from, Carbon $to)
    {
        $query = $this->hierarchyService()
            ->applyVisitScope(SiteVisit::query(), $this->membership($request))
            ->whereBetween('scheduled_at', [$from, $to]);

        return $this->filters($request, $query, 'team_id', 'assigned_to');
    }

    private function reportTaskScope(Request $request, Carbon $from, Carbon $to)
    {
        $query = $this->hierarchyService()
            ->applyTaskScope(Task::query(), $this->membership($request))
            ->whereBetween('created_at', [$from, $to]);

        return $this->filters($request, $query, 'team_id', 'assigned_to');
    }

    private function trend(Request $request, Carbon $from, Carbon $to, string $groupBy): array
    {
        $raw = [];

        foreach ((clone $this->reportLeadScope($request, $from, $to))->get(['created_at', 'won_at', 'enterprise_stage', 'deal_value']) as $lead) {
            if ($lead->created_at) {
                $key = $this->bucket($lead->created_at, $groupBy);
                $raw[$key]['leads'] = ($raw[$key]['leads'] ?? 0) + 1;
            }

            if ($lead->enterprise_stage === 'won' && $lead->won_at && $lead->won_at->between($from, $to)) {
                $key = $this->bucket($lead->won_at, $groupBy);
                $raw[$key]['won'] = ($raw[$key]['won'] ?? 0) + 1;
                $raw[$key]['deal_value'] = ($raw[$key]['deal_value'] ?? 0) + (float) $lead->deal_value;
            }
        }

        ksort($raw);

        return collect($raw)->map(fn ($values, $key) => [
            'period' => $key,
            'leads' => (int) ($values['leads'] ?? 0),
            'won' => (int) ($values['won'] ?? 0),
            'deal_value' => (float) ($values['deal_value'] ?? 0),
        ])->values()->all();
    }

    private function bucket(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }

    private function teamPerformance(Request $request, Carbon $from, Carbon $to): array
    {
        $membership = $this->membership($request);
        $scopeUserIds = $this->hierarchyService()->scopeUserIds($membership);
        $managedTeamIds = $membership->isManager()
            ? $this->hierarchyService()->managedTeamIds($membership)
            : [];

        $teams = Team::where('enterprise_id', $membership->enterprise_id)
            ->when($request->filled('team_id'), fn ($q) => $q->where('id', $request->integer('team_id')))
            ->when(!$membership->isAdmin(), function ($q) use ($scopeUserIds, $managedTeamIds) {
                $q->where(function ($inner) use ($scopeUserIds, $managedTeamIds) {
                    $inner->whereIn('id', TeamMembership::whereIn('user_id', $scopeUserIds)->select('team_id'));

                    if ($managedTeamIds) {
                        $inner->orWhereIn('id', $managedTeamIds);
                    }
                });
            })
            ->withCount(['memberships'])
            ->withCount(['leads as leads_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->withCount(['leads as won_count' => fn ($q) => $q->where('enterprise_stage', 'won')->whereBetween('won_at', [$from, $to])])
            ->withSum(['leads as deal_value' => fn ($q) => $q->where('enterprise_stage', 'won')->whereBetween('won_at', [$from, $to])], 'deal_value')
            ->orderByDesc('deal_value')
            ->get();

        return $teams->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'members' => (int) $team->memberships_count,
            'leads' => (int) $team->leads_count,
            'won' => (int) $team->won_count,
            'deal_value' => (float) ($team->deal_value ?? 0),
        ])->all();
    }

    private function employeePerformance(Request $request, Carbon $from, Carbon $to): array
    {
        $membership = $this->membership($request);

        $members = EnterpriseMembership::where('enterprise_id', $membership->enterprise_id)
            ->where('status', 'active')
            ->whereIn('role', ['manager', 'employee'])
            ->whereIn('id', $this->hierarchyService()->scopeIds($membership))
            ->with('user')
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $this->assertUserInScope($request, $request->integer('user_id'));
                $q->where('user_id', $request->integer('user_id'));
            })
            ->get();

        $userIds = $members->pluck('user_id');

        $rows = Lead::where('enterprise_id', $membership->enterprise_id)
            ->whereIn('assigned_to', $userIds)
            ->whereBetween('created_at', [$from, $to])
            ->select(
                'assigned_to',
                DB::raw('COUNT(*) as leads_count'),
                DB::raw("SUM(CASE WHEN enterprise_stage = 'won' THEN 1 ELSE 0 END) as won_count"),
                DB::raw("SUM(CASE WHEN enterprise_stage = 'won' THEN deal_value ELSE 0 END) as deal_value")
            )
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $visits = SiteVisit::where('enterprise_id', $membership->enterprise_id)
            ->whereIn('assigned_to', $userIds)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->select('assigned_to', DB::raw('COUNT(*) as completed_visits'))
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        return $members->map(fn (EnterpriseMembership $member) => [
            'user_id' => $member->user_id,
            'name' => $member->user?->name,
            'role' => $member->role,
            'leads' => (int) ($rows[$member->user_id]->leads_count ?? 0),
            'won' => (int) ($rows[$member->user_id]->won_count ?? 0),
            'deal_value' => (float) ($rows[$member->user_id]->deal_value ?? 0),
            'completed_visits' => (int) ($visits[$member->user_id]->completed_visits ?? 0),
        ])->sortByDesc('deal_value')->values()->all();
    }

    private function campaignPerformance(Request $request, Carbon $from, Carbon $to): array
    {
        $membership = $this->membership($request);
        $scopeUserIds = $this->hierarchyService()->scopeUserIds($membership);
        $managedTeamIds = $membership->isManager()
            ? $this->hierarchyService()->managedTeamIds($membership)
            : [];

        return Campaign::where('enterprise_id', $membership->enterprise_id)
            ->when(!$membership->isAdmin(), function ($q) use ($scopeUserIds, $managedTeamIds) {
                $q->where(function ($inner) use ($scopeUserIds, $managedTeamIds) {
                    $inner->whereHas('leads', fn ($lead) => $lead->whereIn('assigned_to', $scopeUserIds));

                    if ($managedTeamIds) {
                        $inner->orWhereIn('team_id', $managedTeamIds);
                    }
                });
            })
            ->withCount(['leads' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->withCount(['leads as won_count' => fn ($q) => $q->where('enterprise_stage', 'won')->whereBetween('won_at', [$from, $to])])
            ->withSum(['leads as deal_value' => fn ($q) => $q->where('enterprise_stage', 'won')->whereBetween('won_at', [$from, $to])], 'deal_value')
            ->orderByDesc('leads_count')
            ->get()
            ->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'leads' => (int) $campaign->leads_count,
                'won' => (int) $campaign->won_count,
                'deal_value' => (float) ($campaign->deal_value ?? 0),
            ])
            ->all();
    }

    private function activityBreakdown(Request $request, Carbon $from, Carbon $to, $leads): array
    {
        return LeadActivity::where('enterprise_id', $this->enterprise($request)->id)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('lead_id', (clone $leads)->select('id'))
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['type' => $row->type, 'count' => (int) $row->count])
            ->all();
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

        return (float) match ($target->metric) {
            'leads_assigned' => (clone $leads)->whereBetween('assigned_at', [$start, $end])->count(),
            'leads_contacted' => (clone $leads)->whereBetween('last_contacted_at', [$start, $end])->count(),
            'leads_qualified' => LeadActivity::where('enterprise_id', $target->enterprise_id)
                ->where('type', 'status_changed')->where('meta->to', 'qualified')
                ->whereBetween('created_at', [$start, $end])->whereIn('lead_id', (clone $leads)->select('id'))->count(),
            'site_visits_completed' => (clone $visits)->where('status', 'completed')->whereBetween('completed_at', [$start, $end])->count(),
            'deals_won' => (clone $leads)->where('enterprise_stage', 'won')->whereBetween('won_at', [$start, $end])->count(),
            'deal_value' => (clone $leads)->where('enterprise_stage', 'won')->whereBetween('won_at', [$start, $end])->sum('deal_value'),
            default => 0,
        };
    }
}
