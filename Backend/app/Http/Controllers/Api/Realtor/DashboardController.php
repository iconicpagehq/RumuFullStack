<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CallResource;
use App\Http\Resources\CampaignResource;
use App\Models\Lead;
use App\Models\RealtorSiteVisit;
use App\Models\RealtorTask;
use App\Services\Calls\CallAccessService;
use App\Services\Calls\CallMetricsService;
use App\Services\PlanExpiryService;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function index(Request $request, PlanExpiryService $planExpiry)
    {
        $user = $request->user();
        $campaigns = $user->campaigns()->with('leads')->get();

        $campaignStatusData = [
            'active' => $campaigns->where('status', 'active')->count(),
            'paused' => $campaigns->where('status', 'paused')->count(),
            'completed' => $campaigns->where('status', 'completed')->count(),
        ];

        $allLeads = $campaigns->flatMap->leads;
        $leadStatusData = [
            'new' => $allLeads->where('status', 'new')->count(),
            'contacted' => $allLeads->where('status', 'contacted')->count(),
            'qualified' => $allLeads->where('status', 'qualified')->count(),
            'proposal' => $allLeads->where('status', 'proposal')->count(),
            'negotiation' => $allLeads->where('status', 'negotiation')->count(),
            'won' => $allLeads->where('status', 'won')->count(),
            'lost' => $allLeads->where('status', 'lost')->count(),
        ];

        $months = [];
        $monthlyLeads = [];
        $monthlyCampaigns = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');

            $monthlyLeads[] = $allLeads->filter(
                fn ($lead) => $lead->created_at->format('Y-m') === $date->format('Y-m')
            )->count();

            $monthlyCampaigns[] = $campaigns->filter(
                fn ($campaign) => $campaign->created_at->format('Y-m') === $date->format('Y-m')
            )->count();
        }

        $recentCampaigns = $campaigns->sortByDesc('created_at')->take(5)->values();

        $upcomingTasks = RealtorTask::forUser($user->id)
            ->with('lead:id,name')
            ->whereIn('status', [RealtorTask::STATUS_PENDING, RealtorTask::STATUS_IN_PROGRESS])
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn (RealtorTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority,
                'due_at' => $task->due_at?->toIso8601String(),
                'lead' => $task->lead?->name,
            ]);

        $overdueTasks = RealtorTask::forUser($user->id)
            ->with('lead:id,name')
            ->whereIn('status', [RealtorTask::STATUS_PENDING, RealtorTask::STATUS_IN_PROGRESS])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn (RealtorTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority,
                'due_at' => $task->due_at?->toIso8601String(),
                'lead' => $task->lead?->name,
            ]);

        $upcomingVisits = RealtorSiteVisit::forUser($user->id)
            ->with(['lead:id,name', 'project:id,name'])
            ->where('status', RealtorSiteVisit::STATUS_SCHEDULED)
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn (RealtorSiteVisit $visit) => [
                'id' => $visit->id,
                'scheduled_at' => $visit->scheduled_at?->toIso8601String(),
                'lead' => $visit->lead?->name,
                'project' => $visit->project?->name,
            ]);

        $followUps = Lead::whereNull('enterprise_id')
            ->forRealtor($user->id)
            ->with('campaign:id,name')
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '>=', now())
            ->orderBy('next_follow_up_at')
            ->limit(5)
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'next_follow_up_at' => $lead->next_follow_up_at?->toIso8601String(),
                'campaign' => $lead->campaign?->name,
            ]);

        $calls = app(CallMetricsService::class)->payload(
            app(CallAccessService::class)->realtorQuery($user->id),
            5
        );
        $calls['recent'] = CallResource::collection($calls['recent'])->resolve();

        return $this->success([
            'campaign_status' => $campaignStatusData,
            'lead_status' => $leadStatusData,
            'months' => $months,
            'monthly_leads' => $monthlyLeads,
            'monthly_campaigns' => $monthlyCampaigns,
            'recent_campaigns' => CampaignResource::collection($recentCampaigns),
            'plan_expiry_warning' => $planExpiry->getExpiryWarning($user),
            'upcoming_tasks' => $upcomingTasks,
            'overdue_tasks' => $overdueTasks,
            'overdue_tasks_count' => RealtorTask::forUser($user->id)
                ->whereIn('status', [RealtorTask::STATUS_PENDING, RealtorTask::STATUS_IN_PROGRESS])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
            'upcoming_site_visits' => $upcomingVisits,
            'lead_follow_ups' => $followUps,
            'calls' => $calls,
        ]);
    }
}
