<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseCampaignResource;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Team;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnterpriseCampaignController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $query = Campaign::where('enterprise_id', $enterprise->id)
            ->with(['team', 'user'])
            ->withCount('leads')
            ->withCount(['leads as won_deals' => fn ($inner) => $inner->where('enterprise_stage', 'won')])
            ->withSum(['leads as deal_value' => fn ($inner) => $inner->where('enterprise_stage', 'won')], 'deal_value');

        if ($membership->isManager()) {
            $query->where(function ($inner) use ($request) {
                $inner->whereIn('team_id', $this->teamScopeIds($request))
                    ->orWhere('user_id', $request->user()->id);
            });
        } elseif ($membership->isEmployee()) {
            $teamId = $membership->primaryTeamMembership?->team_id;
            $query->where('team_id', $teamId ?? 0);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $this->paginated(EnterpriseCampaignResource::collection(
            $query->latest()->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function store(
        Request $request,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin', 'manager']);
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);
        $limits->ensure($enterprise, 'campaigns');

        $validated = $this->validateCampaign($request);

        if ($membership->isManager()) {
            $teamIds = $this->teamScopeIds($request);
            if (empty($validated['team_id']) || !in_array((int) $validated['team_id'], $teamIds, true)) {
                throw ValidationException::withMessages(['team_id' => ['Managers can only create campaigns for their own team.']]);
            }
        }

        if (!empty($validated['team_id'])) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
        }

        $campaign = Campaign::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'enterprise_id' => $enterprise->id,
        ]);

        $audit->log($enterprise, $request->user(), 'campaign.created', $campaign, 'Campaign created');

        return $this->success(
            new EnterpriseCampaignResource($campaign->load(['team', 'user'])->loadCount('leads')),
            'Campaign created.',
            201
        );
    }

    public function show(Request $request, Campaign $campaign)
    {
        $this->ensureCampaignVisible($request, $campaign);

        $campaign->load(['team', 'user'])
            ->loadCount('leads')
            ->loadCount(['leads as won_deals' => fn ($inner) => $inner->where('enterprise_stage', 'won')])
            ->loadSum(['leads as deal_value' => fn ($inner) => $inner->where('enterprise_stage', 'won')], 'deal_value');

        return $this->success(new EnterpriseCampaignResource($campaign));
    }

    public function update(Request $request, Campaign $campaign, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $this->ensureCampaignVisible($request, $campaign);

        $validated = $this->validateCampaign($request);
        $membership = $this->membership($request);

        if ($membership->isManager()) {
            $teamIds = $this->teamScopeIds($request);
            if (empty($validated['team_id']) || !in_array((int) $validated['team_id'], $teamIds, true)) {
                throw ValidationException::withMessages(['team_id' => ['Managers can only assign campaigns to their own team.']]);
            }
        }

        if (!empty($validated['team_id'])) {
            $team = Team::where('enterprise_id', $this->membership($request)->enterprise_id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
        }

        $campaign->update($validated);
        $audit->log($this->enterprise($request), $request->user(), 'campaign.updated', $campaign);

        return $this->success(
            new EnterpriseCampaignResource($campaign->fresh()->load(['team', 'user'])->loadCount('leads')),
            'Campaign updated.'
        );
    }

    public function destroy(Request $request, Campaign $campaign, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureCampaignVisible($request, $campaign);

        if (Lead::where('campaign_id', $campaign->id)->exists()) {
            $campaign->update(['status' => 'completed']);
            return $this->success(null, 'Campaign archived because it has attributed leads.');
        }

        $campaign->delete();
        $audit->log($this->enterprise($request), $request->user(), 'campaign.deleted', $campaign);

        return $this->success(null, 'Campaign deleted.');
    }

    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,paused,completed',
            'team_id' => 'nullable|integer',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
    }

    private function ensureCampaignVisible(Request $request, Campaign $campaign): void
    {
        $membership = $this->membership($request);
        abort_if($campaign->enterprise_id !== $membership->enterprise_id, 404);

        if ($membership->isAdmin()) {
            return;
        }

        if ($membership->isManager()) {
            $visible = in_array((int) $campaign->team_id, $this->teamScopeIds($request), true)
                || (int) $campaign->user_id === (int) $membership->user_id;
            abort_if(!$visible, 403, 'This campaign is outside your scope.');
            return;
        }

        $teamId = $membership->primaryTeamMembership?->team_id;
        abort_if((int) $campaign->team_id !== (int) $teamId, 403, 'This campaign is outside your scope.');
    }
}
