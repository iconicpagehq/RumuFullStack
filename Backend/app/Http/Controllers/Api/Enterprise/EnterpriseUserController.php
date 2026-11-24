<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseAuditLogResource;
use App\Http\Resources\EnterpriseMembershipResource;
use App\Models\EnterpriseAuditLog;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EnterpriseUserController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);
        $hierarchy = $this->hierarchyService();

        $query = EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->notRemoved()
            ->with(['user', 'primaryTeamMembership.team', 'reportsTo.user'])
            ->withCount(['directReports as direct_reports_count' => fn ($inner) => $inner->notRemoved()]);

        if (!$membership->isAdmin()) {
            $query->whereIn('id', $hierarchy->scopeIds($membership));
        }

        if ($request->has('reports_to_id')) {
            $reportsTo = $request->input('reports_to_id');

            if ($reportsTo === null || $reportsTo === '' || $reportsTo === 'null' || $reportsTo === 'none') {
                $query->whereNull('reports_to_membership_id');
            } else {
                $query->where('reports_to_membership_id', (int) $reportsTo);
            }
        }

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($teamId = $request->input('team_id')) {
            $query->whereHas('teamMemberships', fn ($inner) => $inner->where('team_id', $teamId));
        }

        $statsQuery = EnterpriseMembership::where('enterprise_id', $enterprise->id)->notRemoved();

        if (!$membership->isAdmin()) {
            $statsQuery->whereIn('id', $hierarchy->scopeIds($membership));
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'suspended' => (clone $statsQuery)->where('status', 'suspended')->count(),
            'admins' => (clone $statsQuery)->where('role', 'admin')->count(),
            'managers' => (clone $statsQuery)->where('role', 'manager')->count(),
            'employees' => (clone $statsQuery)->where('role', 'employee')->count(),
        ];

        $paginator = $query->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")->paginate(
            (int) $request->input('per_page', 20)
        );

        $paginator->getCollection()->each(function (EnterpriseMembership $member) use ($hierarchy) {
            $member->subtree_size = $hierarchy->subtreeSize($member);
        });

        return response()->json([
            'success' => true,
            'data' => EnterpriseMembershipResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'stats' => $stats,
            ],
            'message' => null,
        ]);
    }

    public function store(
        Request $request,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:manager,employee',
            'designation' => 'nullable|string|max:120',
            'employee_code' => 'nullable|string|max:50',
            'team_id' => 'nullable|integer',
            'reports_to_membership_id' => 'nullable|integer',
        ]);

        $team = null;
        if (!empty($validated['team_id'])) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
        }

        $hierarchy = $this->hierarchyService();
        $managerId = $validated['reports_to_membership_id'] ?? $this->membership($request)->id;
        $manager = EnterpriseMembership::where('enterprise_id', $enterprise->id)->find($managerId);
        $hierarchy->assertSelectableManager($enterprise, $manager);

        $limits->ensure($enterprise, $validated['role'] === 'manager' ? 'managers' : 'employees');

        $membership = DB::transaction(function () use ($validated, $enterprise, $team, $request, $audit, $manager) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'enterprise',
                'plan_id' => $enterprise->plan_id,
            ]);

            $membership = EnterpriseMembership::create([
                'enterprise_id' => $enterprise->id,
                'user_id' => $user->id,
                'role' => $validated['role'],
                'status' => EnterpriseMembership::STATUS_ACTIVE,
                'designation' => $validated['designation'] ?? null,
                'employee_code' => $validated['employee_code'] ?? null,
                'joined_at' => now(),
                'invited_by' => $request->user()->id,
                'reports_to_membership_id' => $manager?->id,
            ]);

            if ($team) {
                TeamMembership::create([
                    'enterprise_id' => $enterprise->id,
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'is_primary' => true,
                    'joined_at' => now(),
                ]);
            }

            $audit->log($enterprise, $request->user(), 'member.created', $membership, 'Team member added', [
                'role' => $membership->role,
                'team_id' => $team?->id,
                'reports_to_membership_id' => $manager?->id,
            ]);

            return $membership;
        });

        $this->hierarchyService()->flush($enterprise->id);

        return $this->success(
            new EnterpriseMembershipResource($membership->fresh()->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])),
            'Team member added.',
            201
        );
    }

    public function show(Request $request, EnterpriseMembership $membership)
    {
        $this->ensureMembershipVisible($request, $membership);

        $hierarchy = $this->hierarchyService();

        $membership->load([
            'user',
            'primaryTeamMembership.team',
            'inviter',
            'reportsTo.user',
            'directReports' => fn ($query) => $query->notRemoved()
                ->with(['user', 'primaryTeamMembership.team'])
                ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")
                ->orderBy('id'),
        ]);

        $membership->loadCount(['directReports as direct_reports_count' => fn ($query) => $query->notRemoved()]);
        $membership->subtree_size = $hierarchy->subtreeSize($membership);

        $stats = [
            'assigned_leads' => Lead::where('enterprise_id', $membership->enterprise_id)->where('assigned_to', $membership->user_id)->count(),
            'open_leads' => Lead::where('enterprise_id', $membership->enterprise_id)->where('assigned_to', $membership->user_id)
                ->whereNotIn('enterprise_stage', ['won', 'lost'])->count(),
            'won_deals' => Lead::where('enterprise_id', $membership->enterprise_id)->where('assigned_to', $membership->user_id)
                ->where('enterprise_stage', 'won')->count(),
            'deal_value' => (float) Lead::where('enterprise_id', $membership->enterprise_id)->where('assigned_to', $membership->user_id)
                ->where('enterprise_stage', 'won')->sum('deal_value'),
        ];

        return $this->success([
            'membership' => new EnterpriseMembershipResource($membership),
            'stats' => $stats,
            'hierarchy' => [
                'can_manage_hierarchy' => $hierarchy->canManageHierarchy($this->membership($request)),
                'scope' => $hierarchy->scopeMode($this->membership($request)),
                'direct_reports_count' => (int) ($membership->direct_reports_count ?? 0),
                'subtree_size' => $membership->subtree_size,
            ],
        ]);
    }

    public function history(Request $request, EnterpriseMembership $membership)
    {
        $this->requireRole($request, ['admin']);
        $this->ensureMembershipVisible($request, $membership);

        $logs = EnterpriseAuditLog::where('enterprise_id', $membership->enterprise_id)
            ->where(function ($query) use ($membership) {
                $query->where(fn ($q) => $q->where('subject_type', User::class)->where('subject_id', $membership->user_id))
                    ->orWhere(fn ($q) => $q->where('subject_type', EnterpriseMembership::class)->where('subject_id', $membership->id))
                    ->orWhere('user_id', $membership->user_id);
            })
            ->with('user')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => EnterpriseAuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function update(
        Request $request,
        EnterpriseMembership $membership,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureMembershipVisible($request, $membership);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'role' => 'sometimes|required|in:manager,employee',
            'designation' => 'nullable|string|max:120',
            'employee_code' => 'nullable|string|max:50',
            'team_id' => 'nullable|integer',
        ]);

        if ($membership->role === 'admin') {
            if (isset($validated['role']) && $validated['role'] !== 'admin') {
                throw ValidationException::withMessages(['role' => ['The company administrator role cannot be changed.']]);
            }

            unset($validated['role']);
        }

        if (isset($validated['role']) && $validated['role'] !== $membership->role) {
            $resource = $validated['role'] === 'manager' ? 'managers' : 'employees';
            $limits->ensure($enterprise, $resource);
            $membership->role = $validated['role'];
        }

        if (isset($validated['name']) || array_key_exists('phone', $validated)) {
            $membership->user->update(collect($validated)->only(['name', 'phone'])->all());
        }

        $membership->fill(collect($validated)->only(['designation', 'employee_code'])->all());
        $membership->save();

        if (array_key_exists('team_id', $validated)) {
            $this->moveToTeam($membership, $validated['team_id'], $enterprise->id);
        }

        $audit->log($enterprise, $request->user(), 'member.updated', $membership, 'Membership updated', [
            'role' => $membership->role,
            'status' => $membership->status,
        ]);

        $this->hierarchyService()->flush($enterprise->id);

        return $this->success(
            new EnterpriseMembershipResource($membership->fresh()->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])),
            'Team member updated.'
        );
    }

    public function suspend(
        Request $request,
        EnterpriseMembership $membership,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureMembershipVisible($request, $membership);

        if ($membership->user_id === $request->user()->id) {
            return $this->error('You cannot suspend your own account.', 422);
        }

        if ($membership->role === 'admin') {
            return $this->error('The company owner cannot be suspended.', 422);
        }

        $membership->update([
            'status' => EnterpriseMembership::STATUS_SUSPENDED,
            'suspended_at' => now(),
        ]);

        $audit->log($this->enterprise($request), $request->user(), 'member.suspended', $membership);

        $this->hierarchyService()->flush($membership->enterprise_id);

        return $this->success(new EnterpriseMembershipResource($membership->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])), 'Team member suspended.');
    }

    public function resume(
        Request $request,
        EnterpriseMembership $membership,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureMembershipVisible($request, $membership);

        $membership->update(['status' => EnterpriseMembership::STATUS_ACTIVE, 'suspended_at' => null]);

        $audit->log($this->enterprise($request), $request->user(), 'member.resumed', $membership);

        $this->hierarchyService()->flush($membership->enterprise_id);

        return $this->success(new EnterpriseMembershipResource($membership->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])), 'Team member reactivated.');
    }

    public function transfer(
        Request $request,
        EnterpriseMembership $membership,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin', 'manager']);
        $this->ensureMembershipVisible($request, $membership);
        $enterprise = $this->enterprise($request);
        $actor = $this->membership($request);

        if ($membership->role === 'admin') {
            return $this->error('The company owner cannot be transferred.', 422);
        }

        $validated = $request->validate([
            'team_id' => 'required|integer',
            'lead_action' => 'required|in:retain,transfer,unassign',
            'destination_user_id' => 'nullable|integer',
        ]);

        $team = Team::where('enterprise_id', $enterprise->id)->find($validated['team_id']);
        if (!$team) {
            throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
        }

        if ($actor->isManager() && !in_array((int) $team->id, $this->teamScopeIds($request), true)) {
            abort(403, 'Managers can only transfer members within their own teams.');
        }

        $destination = null;
        if ($validated['lead_action'] === 'transfer') {
            if (empty($validated['destination_user_id'])) {
                throw ValidationException::withMessages([
                    'destination_user_id' => ['Select the agent who will receive the leads.'],
                ]);
            }

            $destination = User::where('role', 'enterprise')
                ->whereKey($validated['destination_user_id'])
                ->whereHas('enterpriseMembership', function ($query) use ($enterprise, $team) {
                    $query->where('enterprise_id', $enterprise->id)
                        ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                        ->whereHas('teamMemberships', fn ($teamQuery) => $teamQuery->where('team_id', $team->id));
                })
                ->first();

            if (!$destination) {
                throw ValidationException::withMessages([
                    'destination_user_id' => ['Select an active agent from the destination team.'],
                ]);
            }
        }

        DB::transaction(function () use ($membership, $team, $enterprise, $validated, $destination, $request, $audit) {
            $this->moveToTeam($membership, $team->id, $enterprise->id);

            $leads = Lead::where('enterprise_id', $enterprise->id)
                ->where('assigned_to', $membership->user_id)
                ->whereNotIn('enterprise_stage', ['won', 'lost']);

            if ($validated['lead_action'] === 'unassign') {
                $leads->update([
                    'assigned_to' => null,
                    'assigned_at' => null,
                    'team_id' => $team->id,
                    'assigned_manager_id' => $team->manager_id,
                ]);
            } elseif ($validated['lead_action'] === 'transfer' && $destination) {
                $leads->update([
                    'assigned_to' => $destination->id,
                    'assigned_at' => now(),
                    'team_id' => $team->id,
                    'assigned_manager_id' => $team->manager_id,
                ]);
            } else {
                $leads->update([
                    'team_id' => $team->id,
                    'assigned_manager_id' => $team->manager_id,
                ]);
            }

            $audit->log($enterprise, $request->user(), 'member.transferred', $membership, 'Team member transferred', [
                'team_id' => $team->id,
                'lead_action' => $validated['lead_action'],
                'destination_user_id' => $destination?->id,
            ]);
        });

        $this->hierarchyService()->flush($enterprise->id);

        return $this->success(
            new EnterpriseMembershipResource($membership->fresh()->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])),
            'Team member transferred.'
        );
    }

    public function remove(
        Request $request,
        EnterpriseMembership $membership,
        EnterpriseAuditService $audit,
        \App\Services\PlatformAuditService $platformAudit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureMembershipVisible($request, $membership);
        $enterprise = $this->enterprise($request);

        if ((int) $membership->user_id === (int) $request->user()->id) {
            return $this->error('You cannot remove your own account.', 422);
        }

        if ($membership->role === EnterpriseMembership::ROLE_ADMIN) {
            $activeAdmins = EnterpriseMembership::where('enterprise_id', $enterprise->id)
                ->where('role', EnterpriseMembership::ROLE_ADMIN)
                ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                ->count();

            if ($activeAdmins <= 1) {
                return $this->error('The last active administrator cannot be removed.', 422);
            }
        }

        $hierarchy = $this->hierarchyService();
        $directReportsCount = $hierarchy->directReportsCount($membership);

        $validated = $request->validate([
            'lead_action' => 'required|in:transfer,unassign',
            'transfer_to' => 'nullable|integer',
            'reports_action' => 'nullable|in:reparent_to_manager,reparent_to_admin,reassign',
            'reassign_to_membership_id' => 'nullable|integer',
        ]);

        if ($directReportsCount > 0 && empty($validated['reports_action'])) {
            throw ValidationException::withMessages([
                'reports_action' => ['Choose what happens to this member\'s direct reports.'],
            ]);
        }

        if (($validated['reports_action'] ?? null) === 'reassign' && empty($validated['reassign_to_membership_id'])) {
            throw ValidationException::withMessages([
                'reassign_to_membership_id' => ['Select the member who will receive the direct reports.'],
            ]);
        }

        $destination = null;
        if ($validated['lead_action'] === 'transfer') {
            if (empty($validated['transfer_to'])) {
                throw ValidationException::withMessages([
                    'transfer_to' => ['Select the agent who will receive the leads.'],
                ]);
            }

            $destination = User::where('role', 'enterprise')
                ->whereKey($validated['transfer_to'])
                ->whereHas('enterpriseMembership', function ($query) use ($enterprise, $membership) {
                    $query->where('enterprise_id', $enterprise->id)
                        ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                        ->where('user_id', '!=', $membership->user_id);
                })
                ->first();

            if (!$destination) {
                throw ValidationException::withMessages([
                    'transfer_to' => ['Select an active agent from this enterprise.'],
                ]);
            }
        }

        $counts = DB::transaction(function () use ($membership, $enterprise, $validated, $destination, $request, $audit, $platformAudit, $hierarchy, $directReportsCount) {
            $reportsMoved = [];

            if ($directReportsCount > 0) {
                $reportsMoved = $hierarchy->reparentOrphans(
                    $membership,
                    $validated['reports_action'],
                    isset($validated['reassign_to_membership_id']) ? (int) $validated['reassign_to_membership_id'] : null
                );
            }

            $destinationTeam = $destination?->enterpriseMembership
                ? TeamMembership::where('enterprise_id', $enterprise->id)->where('user_id', $destination->id)->first()
                : null;

            $leads = Lead::where('enterprise_id', $enterprise->id)
                ->where('assigned_to', $membership->user_id)
                ->whereNotIn('enterprise_stage', ['won', 'lost']);

            $leadCount = (clone $leads)->count();

            if ($validated['lead_action'] === 'transfer' && $destination) {
                $leads->update([
                    'assigned_to' => $destination->id,
                    'assigned_at' => now(),
                    'team_id' => $destinationTeam?->team_id,
                    'assigned_manager_id' => $destinationTeam?->team?->manager_id,
                ]);
            } else {
                $leads->update([
                    'assigned_to' => null,
                    'assigned_at' => null,
                ]);
            }

            TeamMembership::where('enterprise_id', $enterprise->id)
                ->where('user_id', $membership->user_id)
                ->delete();

            $membership->update([
                'status' => EnterpriseMembership::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

            $membership->user->tokens()->delete();

            $audit->log($enterprise, $request->user(), 'member.removed', $membership, 'Team member removed', [
                'lead_action' => $validated['lead_action'],
                'transferred_to' => $destination?->id,
                'leads_moved' => $leadCount,
                'direct_reports_count' => $directReportsCount,
                'reports_action' => $validated['reports_action'] ?? null,
                'reports_moved' => count($reportsMoved),
                'reassigned_to_membership_id' => $validated['reassign_to_membership_id'] ?? null,
            ]);

            $platformAudit->log($request->user(), 'enterprise.member_removed', $membership, 'Enterprise member removed', [
                'enterprise_id' => $enterprise->id,
                'user_id' => $membership->user_id,
                'lead_action' => $validated['lead_action'],
                'leads_moved' => $leadCount,
                'reports_moved' => count($reportsMoved),
            ]);

            return ['leads_moved' => $leadCount, 'reports_moved' => count($reportsMoved)];
        });

        $this->hierarchyService()->flush($enterprise->id);

        return $this->success([
            'membership_id' => $membership->id,
            ...$counts,
        ], 'Team member removed.');
    }

    private function moveToTeam(EnterpriseMembership $membership, ?int $teamId, int $enterpriseId): void
    {
        if ($teamId === null) {
            TeamMembership::where('enterprise_id', $enterpriseId)->where('user_id', $membership->user_id)->delete();
            return;
        }

        $team = Team::where('enterprise_id', $enterpriseId)->find($teamId);
        if (!$team) {
            throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
        }

        TeamMembership::updateOrCreate(
            ['enterprise_id' => $enterpriseId, 'user_id' => $membership->user_id],
            ['team_id' => $team->id, 'is_primary' => true, 'joined_at' => now()]
        );
    }

    private function ensureMembershipVisible(Request $request, EnterpriseMembership $membership): void
    {
        $actor = $this->membership($request);

        abort_if((int) $membership->enterprise_id !== (int) $actor->enterprise_id, 404);

        abort_if(
            !$this->hierarchyService()->canView($actor, $membership),
            403,
            'This member is outside your reporting scope.'
        );
    }
}
