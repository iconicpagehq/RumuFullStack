<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseMembershipResource;
use App\Models\EnterpriseMembership;
use App\Services\EnterpriseAuditService;
use Illuminate\Http\Request;

class EnterpriseHierarchyController extends EnterpriseApiController
{
    /**
     * Nested organization tree scoped to the viewer.
     *
     * Admins receive every root; managers and lead employees receive their
     * own node as the root; plain employees receive only themselves.
     */
    public function tree(Request $request)
    {
        $membership = $this->membership($request);

        return $this->success(
            $this->hierarchyService()->buildTree($this->enterprise($request), $membership)
        );
    }

    /**
     * Direct reports of a member the viewer is allowed to see.
     */
    public function reports(Request $request, EnterpriseMembership $membership)
    {
        $this->ensureVisible($request, $membership);

        $hierarchy = $this->hierarchyService();
        $reports = $hierarchy->directReports($membership)->map(function (EnterpriseMembership $member) use ($hierarchy) {
            return [
                'membership_id' => (int) $member->id,
                'user_id' => (int) $member->user_id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'role' => $member->role,
                'status' => $member->status,
                'designation' => $member->designation,
                'employee_code' => $member->employee_code,
                'team' => $member->primaryTeamMembership?->team
                    ? [
                        'id' => (int) $member->primaryTeamMembership->team->id,
                        'name' => $member->primaryTeamMembership->team->name,
                    ]
                    : null,
                'is_lead' => $hierarchy->hasReports($member),
                'direct_reports_count' => $hierarchy->directReportsCount($member),
                'subtree_size' => $hierarchy->subtreeSize($member),
                'reports_to_membership_id' => $member->reports_to_membership_id
                    ? (int) $member->reports_to_membership_id
                    : null,
            ];
        })->values();

        return $this->success([
            'membership_id' => (int) $membership->id,
            'direct_reports_count' => $reports->count(),
            'reports' => $reports,
        ]);
    }

    /**
     * Re-parent a member. Company admins only.
     */
    public function changeManager(
        Request $request,
        EnterpriseMembership $membership,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureVisible($request, $membership);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'reports_to_membership_id' => 'nullable|integer',
        ]);

        $manager = null;
        if (!empty($validated['reports_to_membership_id'])) {
            $manager = EnterpriseMembership::where('enterprise_id', $enterprise->id)
                ->find($validated['reports_to_membership_id']);
        }

        $hierarchy = $this->hierarchyService();
        $hierarchy->assertValidManager($membership, $manager);

        $previous = $membership->reports_to_membership_id;

        $membership->update(['reports_to_membership_id' => $manager?->id]);
        $hierarchy->flush($enterprise->id);

        $membership = $membership->fresh()->load([
            'user',
            'primaryTeamMembership.team',
            'reportsTo.user',
        ]);
        $membership->loadCount(['directReports as direct_reports_count' => fn ($query) => $query->notRemoved()]);
        $membership->subtree_size = $hierarchy->subtreeSize($membership);

        $audit->log($enterprise, $request->user(), 'member.manager_changed', $membership, 'Reporting line changed', [
            'previous_membership_id' => $previous ? (int) $previous : null,
            'reports_to_membership_id' => $manager?->id,
            'subtree_size' => $membership->subtree_size,
        ]);

        return $this->success([
            'membership' => new EnterpriseMembershipResource($membership),
            'hierarchy' => [
                'can_manage_hierarchy' => true,
                'scope' => $hierarchy->scopeMode($this->membership($request)),
                'direct_reports_count' => (int) ($membership->direct_reports_count ?? 0),
                'subtree_size' => $membership->subtree_size,
            ],
        ], 'Reporting line updated.');
    }

    private function ensureVisible(Request $request, EnterpriseMembership $membership): void
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
