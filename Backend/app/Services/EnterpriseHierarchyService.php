<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\SiteVisit;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Server-side source of truth for the enterprise reporting hierarchy.
 *
 * The hierarchy is a self-referencing tree on enterprise_memberships
 * (reports_to_membership_id). Admins are roots. Managers see their own
 * reporting subtree plus the teams they manage. Employees with direct
 * reports ("lead employees") see their own subtree; other employees only
 * see themselves. No new membership role is introduced.
 */
class EnterpriseHierarchyService
{
    /** @var array<int, Collection<int, EnterpriseMembership>> */
    private array $cache = [];

    /**
     * All non-removed memberships in the viewer's enterprise, with the
     * relations needed to render an org chart and assignment pickers.
     */
    public function all(EnterpriseMembership $viewer): Collection
    {
        $enterpriseId = (int) $viewer->enterprise_id;

        if (!isset($this->cache[$enterpriseId])) {
            $this->cache[$enterpriseId] = EnterpriseMembership::where('enterprise_id', $enterpriseId)
                ->notRemoved()
                ->with(['user', 'primaryTeamMembership.team'])
                ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->get();
        }

        return $this->cache[$enterpriseId];
    }

    public function flush(?int $enterpriseId = null): void
    {
        if ($enterpriseId === null) {
            $this->cache = [];
            return;
        }

        unset($this->cache[$enterpriseId]);
    }

    public function canManageHierarchy(EnterpriseMembership $viewer): bool
    {
        return $viewer->isAdmin();
    }

    public function hasReports(EnterpriseMembership $membership): bool
    {
        return $this->all($membership)
            ->where('reports_to_membership_id', $membership->id)
            ->isNotEmpty();
    }

    public function isLeadEmployee(EnterpriseMembership $membership): bool
    {
        return $membership->isEmployee() && $this->hasReports($membership);
    }

    public function scopeMode(EnterpriseMembership $viewer): string
    {
        if ($viewer->isAdmin()) {
            return 'all';
        }

        if ($viewer->isManager() || $this->hasReports($viewer)) {
            return 'subtree';
        }

        return 'self';
    }

    /**
     * Membership IDs the viewer is allowed to see and work with.
     *
     * @return array<int>
     */
    public function scopeIds(EnterpriseMembership $viewer): array
    {
        $all = $this->all($viewer);

        if ($viewer->isAdmin()) {
            return $all->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $ids = array_fill_keys($this->descendantIds($viewer, true), true);

        if ($viewer->isManager()) {
            foreach ($this->managedTeamMemberIds($viewer) as $userId) {
                $member = $all->firstWhere('user_id', (int) $userId);
                if (!$member) {
                    continue;
                }

                $ids[(int) $member->id] = true;
                foreach ($this->descendantIds($member) as $id) {
                    $ids[(int) $id] = true;
                }
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * @return array<int>
     */
    public function scopeUserIds(EnterpriseMembership $viewer): array
    {
        $ids = $this->scopeIds($viewer);

        return $this->all($viewer)
            ->whereIn('id', $ids)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * IDs of the viewer plus every member below them in the reporting chain.
     *
     * @return array<int>
     */
    public function descendantIds(EnterpriseMembership $membership, bool $includeSelf = false): array
    {
        $children = $this->childrenMap($membership);
        $ids = $includeSelf ? [(int) $membership->id] : [];
        $queue = $children[$membership->id] ?? [];

        while ($queue) {
            $id = (int) array_shift($queue);

            if (in_array($id, $ids, true)) {
                continue;
            }

            $ids[] = $id;
            foreach ($children[$id] ?? [] as $childId) {
                $queue[] = (int) $childId;
            }
        }

        return $ids;
    }

    /**
     * @return array<int, array<int>>
     */
    private function childrenMap(EnterpriseMembership $viewer): array
    {
        $children = [];

        foreach ($this->all($viewer) as $member) {
            $parentId = $member->reports_to_membership_id;

            if ($parentId !== null) {
                $children[(int) $parentId][] = (int) $member->id;
            }
        }

        return $children;
    }

    /**
     * @return array<int>
     */
    public function managedTeamIds(EnterpriseMembership $membership): array
    {
        return Team::where('enterprise_id', $membership->enterprise_id)
            ->where('manager_id', $membership->user_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int>
     */
    public function managedTeamMemberIds(EnterpriseMembership $membership): array
    {
        $teamIds = $this->managedTeamIds($membership);

        if (!$teamIds) {
            return [];
        }

        return TeamMembership::whereIn('team_id', $teamIds)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canView(EnterpriseMembership $viewer, EnterpriseMembership $target): bool
    {
        if ((int) $viewer->enterprise_id !== (int) $target->enterprise_id || $target->isRemoved()) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        return in_array((int) $target->id, $this->scopeIds($viewer), true);
    }

    public function canAssignTo(EnterpriseMembership $viewer, EnterpriseMembership $target): bool
    {
        if ((int) $viewer->enterprise_id !== (int) $target->enterprise_id) {
            return false;
        }

        if (!$target->isActive() || $target->isAdmin()) {
            return false;
        }

        return in_array((int) $target->id, $this->scopeIds($viewer), true);
    }

    /**
     * Validate a reporting-line change for an existing membership.
     *
     * @throws ValidationException
     */
    public function assertValidManager(EnterpriseMembership $target, ?EnterpriseMembership $manager): void
    {
        if ($target->isAdmin()) {
            if ($manager !== null) {
                throw ValidationException::withMessages([
                    'reports_to_membership_id' => ['Company admins are always at the top of the organization.'],
                ]);
            }

            return;
        }

        if ($manager === null) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['Select the manager this member reports to.'],
            ]);
        }

        if ((int) $manager->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['A member cannot report to themselves.'],
            ]);
        }

        if ((int) $manager->enterprise_id !== (int) $target->enterprise_id) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['Select a manager from this enterprise.'],
            ]);
        }

        if ($manager->isRemoved()) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['This member has been removed and cannot receive reports.'],
            ]);
        }

        if (!$manager->isActive()) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['Select an active manager.'],
            ]);
        }

        if (in_array((int) $manager->id, $this->descendantIds($target, true), true)) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['That change would create a reporting cycle.'],
            ]);
        }
    }

    /**
     * Validate a manager selected while creating or inviting a member.
     *
     * @throws ValidationException
     */
    public function assertSelectableManager(Enterprise $enterprise, ?EnterpriseMembership $manager): void
    {
        if (!$manager
            || (int) $manager->enterprise_id !== (int) $enterprise->id
            || $manager->isRemoved()
            || !$manager->isActive()) {
            throw ValidationException::withMessages([
                'reports_to_membership_id' => ['Select an active manager from this enterprise.'],
            ]);
        }
    }

    public function defaultAdmin(Enterprise $enterprise, ?EnterpriseMembership $exclude = null): ?EnterpriseMembership
    {
        return EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('role', EnterpriseMembership::ROLE_ADMIN)
            ->where('status', EnterpriseMembership::STATUS_ACTIVE)
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude->id))
            ->orderBy('id')
            ->first();
    }

    /**
     * Direct reports of a membership (removed members excluded).
     */
    public function directReports(EnterpriseMembership $membership): Collection
    {
        return $this->all($membership)
            ->where('reports_to_membership_id', $membership->id)
            ->values();
    }

    public function directReportsCount(EnterpriseMembership $membership): int
    {
        return $this->directReports($membership)->count();
    }

    public function subtreeSize(EnterpriseMembership $membership): int
    {
        return count($this->descendantIds($membership, true));
    }

    /**
     * Build the nested organization tree visible to the viewer.
     */
    public function buildTree(Enterprise $enterprise, EnterpriseMembership $viewer): array
    {
        $scopeIds = $this->scopeIds($viewer);
        $models = $this->all($viewer)->keyBy('id');
        // Eloquent collections override only() to match primary keys and reset
        // keys, so filter explicitly to keep the membership IDs as array keys.
        $visible = $models->filter(
            fn (EnterpriseMembership $member) => in_array((int) $member->id, $scopeIds, true)
        );

        if (!$visible->has($viewer->id)) {
            return [
                'scope' => $this->scopeMode($viewer),
                'viewer_membership_id' => $viewer->id,
                'can_manage_hierarchy' => $this->canManageHierarchy($viewer),
                'roots' => [],
            ];
        }

        $children = [];
        foreach ($visible as $member) {
            $parentId = $member->reports_to_membership_id;

            if ($parentId !== null && $visible->has($parentId)) {
                $children[(int) $parentId][] = (int) $member->id;
            }
        }

        foreach ($children as &$ids) {
            usort($ids, function (int $left, int $right) use ($models) {
                return $this->compareNodes($models->get($left), $models->get($right));
            });
        }
        unset($ids);

        if ($viewer->isAdmin()) {
            $roots = $visible
                ->filter(function (EnterpriseMembership $member) use ($visible) {
                    return $member->reports_to_membership_id === null
                        || !$visible->has($member->reports_to_membership_id);
                })
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->all();

            usort($roots, fn (int $left, int $right) => $this->compareNodes($models->get($left), $models->get($right)));
        } else {
            $roots = [(int) $viewer->id];
        }

        $build = function (int $id) use (&$build, $children, $models): array {
            $member = $models->get($id);
            $childNodes = array_map(fn (int $childId) => $build($childId), $children[$id] ?? []);
            $directReports = count($children[$id] ?? []);
            $subtreeSize = 1;

            foreach ($childNodes as $childNode) {
                $subtreeSize += $childNode['subtree_size'];
            }

            return [
                'membership_id' => (int) $member->id,
                'user_id' => (int) $member->user_id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'avatar' => $member->user?->avatar,
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
                'is_lead' => $directReports > 0,
                'direct_reports_count' => $directReports,
                'subtree_size' => $subtreeSize,
                'reports_to_membership_id' => $member->reports_to_membership_id
                    ? (int) $member->reports_to_membership_id
                    : null,
                'children' => $childNodes,
            ];
        };

        return [
            'scope' => $this->scopeMode($viewer),
            'viewer_membership_id' => (int) $viewer->id,
            'can_manage_hierarchy' => $this->canManageHierarchy($viewer),
            'roots' => array_map(fn (int $id) => $build($id), $roots),
        ];
    }

    private function compareNodes(?EnterpriseMembership $left, ?EnterpriseMembership $right): int
    {
        $order = ['admin' => 0, 'manager' => 1, 'employee' => 2];
        $leftRank = $order[$left?->role] ?? 3;
        $rightRank = $order[$right?->role] ?? 3;

        if ($leftRank !== $rightRank) {
            return $leftRank <=> $rightRank;
        }

        return strcasecmp((string) $left?->user?->name, (string) $right?->user?->name);
    }

    /**
     * Move a removed member's direct reports to a safe manager.
     *
     * @return array<int> Membership IDs that were re-parented.
     *
     * @throws ValidationException
     */
    public function reparentOrphans(
        EnterpriseMembership $removed,
        string $action = 'reparent_to_manager',
        ?int $reassignToId = null,
    ): array {
        $reports = $this->directReports($removed);

        if ($reports->isEmpty()) {
            return [];
        }

        $excludedSubtree = $this->descendantIds($removed, true);
        $destination = null;

        if ($action === 'reassign') {
            $destination = EnterpriseMembership::where('enterprise_id', $removed->enterprise_id)
                ->whereKey($reassignToId)
                ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                ->first();

            if (!$destination || in_array((int) $destination->id, $excludedSubtree, true)) {
                throw ValidationException::withMessages([
                    'reassign_to_membership_id' => ['Select an active member outside the removed member\'s reporting line.'],
                ]);
            }
        } elseif ($action === 'reparent_to_admin') {
            $destination = $this->defaultAdmin($removed->enterprise, $removed);
        } else {
            $parentId = $removed->reports_to_membership_id;

            if ($parentId) {
                $destination = EnterpriseMembership::where('enterprise_id', $removed->enterprise_id)
                    ->whereKey($parentId)
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->first();
            }

            $destination ??= $this->defaultAdmin($removed->enterprise, $removed);
        }

        if (!$destination) {
            throw ValidationException::withMessages([
                'reports_action' => ['No active manager is available to receive this member\'s reports.'],
            ]);
        }

        $ids = $reports->pluck('id')->map(fn ($id) => (int) $id)->all();

        EnterpriseMembership::whereIn('id', $ids)->update([
            'reports_to_membership_id' => $destination->id,
        ]);

        $this->flush($removed->enterprise_id);

        return $ids;
    }

    public function applyLeadScope($query, EnterpriseMembership $viewer)
    {
        $query->where('enterprise_id', $viewer->enterprise_id);

        if ($viewer->isAdmin()) {
            return $query;
        }

        $userIds = $this->scopeUserIds($viewer);
        $teamIds = $viewer->isManager() ? $this->managedTeamIds($viewer) : [];

        return $query->where(function ($inner) use ($viewer, $userIds, $teamIds) {
            $inner->whereIn('assigned_to', $userIds);

            if ($teamIds) {
                $inner->orWhereIn('team_id', $teamIds)
                    ->orWhere('assigned_manager_id', $viewer->user_id);
            }
        });
    }

    public function leadVisible(Lead $lead, EnterpriseMembership $viewer): bool
    {
        if ((int) $lead->enterprise_id !== (int) $viewer->enterprise_id) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        if (in_array((int) $lead->assigned_to, $this->scopeUserIds($viewer), true)) {
            return true;
        }

        if (!$viewer->isManager()) {
            return false;
        }

        return in_array((int) $lead->team_id, $this->managedTeamIds($viewer), true)
            || (int) $lead->assigned_manager_id === (int) $viewer->user_id;
    }

    public function applyTaskScope($query, EnterpriseMembership $viewer)
    {
        $query->where('enterprise_id', $viewer->enterprise_id);

        if ($viewer->isAdmin()) {
            return $query;
        }

        $userIds = $this->scopeUserIds($viewer);
        $teamIds = $viewer->isManager() ? $this->managedTeamIds($viewer) : [];

        return $query->where(function ($inner) use ($userIds, $teamIds) {
            $inner->whereIn('assigned_to', $userIds)
                ->orWhereIn('created_by', $userIds);

            if ($teamIds) {
                $inner->orWhereIn('team_id', $teamIds);
            }
        });
    }

    public function taskVisible(Task $task, EnterpriseMembership $viewer): bool
    {
        if ((int) $task->enterprise_id !== (int) $viewer->enterprise_id) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        $userIds = $this->scopeUserIds($viewer);

        if (in_array((int) $task->assigned_to, $userIds, true)
            || in_array((int) $task->created_by, $userIds, true)) {
            return true;
        }

        return $viewer->isManager()
            && in_array((int) $task->team_id, $this->managedTeamIds($viewer), true);
    }

    public function applyVisitScope($query, EnterpriseMembership $viewer)
    {
        $query->where('enterprise_id', $viewer->enterprise_id);

        if ($viewer->isAdmin()) {
            return $query;
        }

        $userIds = $this->scopeUserIds($viewer);
        $teamIds = $viewer->isManager() ? $this->managedTeamIds($viewer) : [];

        return $query->where(function ($inner) use ($userIds, $teamIds) {
            $inner->whereIn('assigned_to', $userIds)
                ->orWhereIn('created_by', $userIds);

            if ($teamIds) {
                $inner->orWhereIn('team_id', $teamIds);
            }
        });
    }

    public function visitVisible(SiteVisit $visit, EnterpriseMembership $viewer): bool
    {
        if ((int) $visit->enterprise_id !== (int) $viewer->enterprise_id) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        $userIds = $this->scopeUserIds($viewer);

        if (in_array((int) $visit->assigned_to, $userIds, true)
            || in_array((int) $visit->created_by, $userIds, true)) {
            return true;
        }

        return $viewer->isManager()
            && in_array((int) $visit->team_id, $this->managedTeamIds($viewer), true);
    }

    public function applyTargetScope($query, EnterpriseMembership $viewer)
    {
        $query->where('enterprise_id', $viewer->enterprise_id);

        if ($viewer->isAdmin()) {
            return $query;
        }

        $userIds = $this->scopeUserIds($viewer);
        $teamIds = $viewer->isManager() ? $this->managedTeamIds($viewer) : [];

        return $query->where(function ($inner) use ($userIds, $teamIds) {
            $inner->whereIn('user_id', $userIds);

            if ($teamIds) {
                $inner->orWhereIn('team_id', $teamIds);
            }
        });
    }

    public function targetVisible($target, EnterpriseMembership $viewer): bool
    {
        if ((int) $target->enterprise_id !== (int) $viewer->enterprise_id) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        if (in_array((int) $target->user_id, $this->scopeUserIds($viewer), true)) {
            return true;
        }

        return $viewer->isManager()
            && in_array((int) $target->team_id, $this->managedTeamIds($viewer), true);
    }
}
