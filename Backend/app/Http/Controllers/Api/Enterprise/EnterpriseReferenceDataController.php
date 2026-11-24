<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseMembershipResource;
use App\Http\Resources\EnterpriseTeamResource;
use App\Models\Campaign;
use App\Models\EnterpriseMembership;
use App\Models\InventoryProject;
use App\Models\Lead;
use App\Models\SalesTarget;
use App\Models\Team;
use App\Models\TeamMembership;
use Illuminate\Http\Request;

class EnterpriseReferenceDataController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);
        $hierarchy = $this->hierarchyService();

        $teams = Team::where('enterprise_id', $enterprise->id)
            ->with('manager')
            ->withCount('memberships');

        if ($membership->isManager()) {
            $teams->where('manager_id', $membership->user_id);
        } elseif ($membership->isEmployee()) {
            $teams->where('id', $membership->primaryTeamMembership?->team_id ?? 0);
        }

        $membersQuery = EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('status', 'active')
            ->with(['user', 'primaryTeamMembership.team', 'reportsTo.user'])
            ->withCount(['directReports as direct_reports_count' => fn ($inner) => $inner->notRemoved()]);

        if (!$membership->isAdmin()) {
            $membersQuery->whereIn('id', $hierarchy->scopeIds($membership));
        }

        $viewer = $membership->load('reportsTo.user');
        $viewer->loadCount(['directReports as direct_reports_count' => fn ($inner) => $inner->notRemoved()]);

        $projects = InventoryProject::where('enterprise_id', $enterprise->id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'location']);

        $campaigns = Campaign::where('enterprise_id', $enterprise->id)
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'team_id']);

        return $this->success([
            'teams' => EnterpriseTeamResource::collection($teams->orderBy('name')->get()),
            'members' => EnterpriseMembershipResource::collection($membersQuery->get()),
            'projects' => $projects,
            'campaigns' => $campaigns,
            'stages' => collect(Lead::ENTERPRISE_STAGES)->map(fn ($stage) => [
                'value' => $stage,
                'label' => ucwords(str_replace('_', ' ', $stage)),
            ]),
            'sources' => Lead::ENTERPRISE_SOURCES,
            'priorities' => ['low', 'medium', 'high', 'urgent'],
            'target_metrics' => SalesTarget::METRICS,
            'roles' => ['manager', 'employee'],
            'permissions' => [
                'role' => $membership->role,
                'can_manage_users' => $membership->isAdmin(),
                'can_manage_teams' => $membership->isAdmin(),
                'can_assign_leads' => !$membership->isEmployee() || $hierarchy->hasReports($membership),
                'can_manage_targets' => !$membership->isEmployee(),
                'can_manage_inventory' => $membership->isAdmin(),
                'can_update_unit_status' => true,
                'can_export_reports' => !$membership->isEmployee(),
                'can_view_hierarchy' => $membership->isAdmin() || $membership->isManager() || $hierarchy->hasReports($membership),
                'can_manage_hierarchy' => $hierarchy->canManageHierarchy($membership),
                'has_reports' => $hierarchy->hasReports($membership),
                'is_lead_employee' => $hierarchy->isLeadEmployee($membership),
                'scope' => $hierarchy->scopeMode($membership),
            ],
            'hierarchy' => [
                'reports_to' => $viewer->reportsTo
                    ? [
                        'membership_id' => (int) $viewer->reportsTo->id,
                        'user_id' => (int) $viewer->reportsTo->user_id,
                        'name' => $viewer->reportsTo->user?->name,
                        'role' => $viewer->reportsTo->role,
                    ]
                    : null,
                'direct_reports_count' => (int) ($viewer->direct_reports_count ?? 0),
                'subtree_size' => $hierarchy->subtreeSize($viewer),
            ],
            'team' => $membership->primaryTeamMembership?->team
                ? new EnterpriseTeamResource($membership->primaryTeamMembership->team->loadCount('memberships'))
                : null,
        ]);
    }
}
