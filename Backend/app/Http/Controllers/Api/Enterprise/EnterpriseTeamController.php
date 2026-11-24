<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseTeamResource;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterpriseTeamController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $query = Team::where('enterprise_id', $enterprise->id)
            ->with(['manager', 'members'])
            ->withCount('memberships')
            ->withCount(['leads as open_leads_count' => fn ($inner) => $inner->whereNotIn('enterprise_stage', ['won', 'lost'])])
            ->withCount(['leads as won_deals_count' => fn ($inner) => $inner->where('enterprise_stage', 'won')])
            ->withSum(['leads as deal_value' => fn ($inner) => $inner->where('enterprise_stage', 'won')], 'deal_value');

        if ($membership->isManager()) {
            $query->where('manager_id', $membership->user_id);
        } elseif ($membership->isEmployee()) {
            $teamId = $membership->primaryTeamMembership?->team_id;
            $query->where('id', $teamId ?? 0);
        }

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return $this->paginated(EnterpriseTeamResource::collection(
            $query->orderBy('name')->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function store(
        Request $request,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);
        $limits->ensure($enterprise, 'teams');

        $validated = $this->validateTeam($request, $enterprise->id, null);

        $team = Team::create([
            'enterprise_id' => $enterprise->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'lead_distribution_mode' => $validated['lead_distribution_mode'] ?? 'manual',
        ]);

        $audit->log($enterprise, $request->user(), 'team.created', $team, 'Team created', [
            'manager_id' => $team->manager_id,
        ]);

        return $this->success(
            new EnterpriseTeamResource($team->load(['manager', 'members'])->loadCount('memberships')),
            'Team created.',
            201
        );
    }

    public function show(Request $request, Team $team)
    {
        $this->ensureTeamVisible($request, $team);

        $team->load(['manager', 'members'])
            ->loadCount('memberships')
            ->loadCount(['leads as open_leads_count' => fn ($inner) => $inner->whereNotIn('enterprise_stage', ['won', 'lost'])])
            ->loadCount(['leads as won_deals_count' => fn ($inner) => $inner->where('enterprise_stage', 'won')])
            ->loadSum(['leads as deal_value' => fn ($inner) => $inner->where('enterprise_stage', 'won')], 'deal_value');

        $members = EnterpriseMembership::where('enterprise_id', $team->enterprise_id)
            ->whereIn('user_id', $team->members->pluck('id'))
            ->with(['user', 'primaryTeamMembership.team'])
            ->get();

        $performance = [
            'assigned_leads' => Lead::where('team_id', $team->id)->count(),
            'won_value' => (float) Lead::where('team_id', $team->id)->where('enterprise_stage', 'won')->sum('deal_value'),
            'overdue_tasks' => Task::where('team_id', $team->id)->overdue()->count(),
            'upcoming_visits' => $team->siteVisits()->upcoming()->count(),
        ];

        return $this->success([
            'team' => new EnterpriseTeamResource($team),
            'members' => \App\Http\Resources\EnterpriseMembershipResource::collection($members),
            'performance' => $performance,
        ]);
    }

    public function update(
        Request $request,
        Team $team,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureTeamVisible($request, $team);
        $enterprise = $this->enterprise($request);

        $validated = $this->validateTeam($request, $enterprise->id, $team->id);

        $previousManager = $team->manager_id;

        $team->update(collect($validated)->only([
            'name', 'description', 'manager_id', 'status', 'lead_distribution_mode',
        ])->all());

        if ($team->manager_id !== $previousManager) {
            Lead::where('team_id', $team->id)
                ->whereNotIn('enterprise_stage', ['won', 'lost'])
                ->update(['assigned_manager_id' => $team->manager_id]);
        }

        $audit->log($enterprise, $request->user(), 'team.updated', $team, 'Team updated', [
            'manager_id' => $team->manager_id,
            'status' => $team->status,
        ]);

        return $this->success(
            new EnterpriseTeamResource($team->fresh()->load(['manager', 'members'])->loadCount('memberships')),
            'Team updated.'
        );
    }

    public function destroy(
        Request $request,
        Team $team,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureTeamVisible($request, $team);

        $openLeads = Lead::where('team_id', $team->id)->whereNotIn('enterprise_stage', ['won', 'lost'])->count();
        if ($openLeads > 0) {
            return $this->error('This team still owns open leads. Reassign them before deleting the team.', 422);
        }

        $enterprise = $this->enterprise($request);

        DB::transaction(function () use ($team, $audit, $request, $enterprise) {
            TeamMembership::where('team_id', $team->id)->delete();
            $team->delete();
            $audit->log($enterprise, $request->user(), 'team.deleted', $team, 'Team deleted');
        });

        return $this->success(null, 'Team deleted.');
    }

    public function addMember(
        Request $request,
        Team $team,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureTeamVisible($request, $team);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $member = EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('user_id', $validated['user_id'])
            ->where('status', 'active')
            ->whereIn('role', ['manager', 'employee'])
            ->first();

        if (!$member) {
            throw ValidationException::withMessages(['user_id' => ['Select an active manager or employee.']]);
        }

        $existing = TeamMembership::where('enterprise_id', $enterprise->id)->where('user_id', $member->user_id)->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'user_id' => [$existing->team_id === $team->id
                    ? 'This member is already on the team.'
                    : 'This member belongs to another team. Use transfer instead.'],
            ]);
        }

        TeamMembership::create([
            'enterprise_id' => $enterprise->id,
            'team_id' => $team->id,
            'user_id' => $member->user_id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $audit->log($enterprise, $request->user(), 'team.member_added', $team, 'Member added to team', [
            'user_id' => $member->user_id,
        ]);

        return $this->success(
            new EnterpriseTeamResource($team->fresh()->load(['manager', 'members'])->loadCount('memberships')),
            'Member added to team.'
        );
    }

    public function removeMember(
        Request $request,
        Team $team,
        User $user,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureTeamVisible($request, $team);
        $enterprise = $this->enterprise($request);

        if ((int) $team->manager_id === (int) $user->id) {
            return $this->error('Assign another team manager before removing this member.', 422);
        }

        $teamMembership = TeamMembership::where('team_id', $team->id)->where('user_id', $user->id)->first();
        if (!$teamMembership) {
            return $this->error('This user is not a member of the team.', 404);
        }

        DB::transaction(function () use ($teamMembership, $team, $enterprise, $audit, $request, $user) {
            $teamMembership->delete();

            Lead::where('team_id', $team->id)
                ->where('assigned_to', $user->id)
                ->whereNotIn('enterprise_stage', ['won', 'lost'])
                ->update(['team_id' => null, 'assigned_manager_id' => null]);

            $audit->log($enterprise, $request->user(), 'team.member_removed', $team, 'Member removed from team', [
                'user_id' => $user->id,
            ]);
        });

        return $this->success(
            new EnterpriseTeamResource($team->fresh()->load(['manager', 'members'])->loadCount('memberships')),
            'Member removed from team.'
        );
    }

    private function validateTeam(Request $request, int $enterpriseId, ?int $ignoreTeamId): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'manager_id' => 'nullable|integer',
            'status' => 'sometimes|in:active,inactive',
            'lead_distribution_mode' => 'sometimes|in:manual,round_robin',
        ]);

        $unique = Team::where('enterprise_id', $enterpriseId)->where('name', $validated['name']);
        if ($ignoreTeamId) {
            $unique->where('id', '!=', $ignoreTeamId);
        }
        if ($unique->exists()) {
            throw ValidationException::withMessages(['name' => ['A team with this name already exists.']]);
        }

        if (!empty($validated['manager_id'])) {
            $manager = EnterpriseMembership::where('enterprise_id', $enterpriseId)
                ->where('user_id', $validated['manager_id'])
                ->where('role', 'manager')
                ->where('status', 'active')
                ->exists();

            if (!$manager) {
                throw ValidationException::withMessages(['manager_id' => ['Select an active manager from this enterprise.']]);
            }
        }

        return $validated;
    }

    private function ensureTeamVisible(Request $request, Team $team): void
    {
        $membership = $this->membership($request);

        abort_if($team->enterprise_id !== $membership->enterprise_id, 404);

        if ($membership->isAdmin()) {
            return;
        }

        if ($membership->isManager()) {
            abort_if((int) $team->manager_id !== (int) $membership->user_id, 403, 'This team is outside your scope.');
            return;
        }

        $teamId = $membership->primaryTeamMembership?->team_id;
        abort_if((int) $team->id !== (int) $teamId, 403, 'This team is outside your scope.');
    }
}
