<?php

namespace App\Console\Commands;

use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Team;
use App\Services\EnterpriseHierarchyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillReportingLines extends Command
{
    protected $signature = 'enterprise:backfill-reporting-lines
        {--dry-run : Preview the changes without writing to the database}
        {--enterprise= : Only backfill a single enterprise ID}';

    protected $description = 'Backfill reports_to_membership_id for enterprise members from teams and admins';

    public function handle(EnterpriseHierarchyService $hierarchy): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Enterprise::query()->orderBy('id');

        if ($this->option('enterprise')) {
            $query->whereKey((int) $this->option('enterprise'));
        }

        $enterprises = $query->get();

        if ($enterprises->isEmpty()) {
            $this->warn('No enterprises matched the selection.');

            return self::SUCCESS;
        }

        $totalUpdated = 0;

        foreach ($enterprises as $enterprise) {
            $updated = $this->backfillEnterprise($enterprise, $dryRun);
            $totalUpdated += $updated;

            $this->line(sprintf(
                'Enterprise #%d (%s): %d membership%s %s',
                $enterprise->id,
                $enterprise->name,
                $updated,
                $updated === 1 ? '' : 's',
                $dryRun ? 'would be updated' : 'updated'
            ));
        }

        $hierarchy->flush();

        $this->info(sprintf(
            '%s %d reporting line%s.',
            $dryRun ? 'Dry run complete.' : 'Backfill complete.',
            $totalUpdated,
            $totalUpdated === 1 ? '' : 's'
        ));

        return self::SUCCESS;
    }

    private function backfillEnterprise(Enterprise $enterprise, bool $dryRun): int
    {
        $memberships = EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('status', '!=', EnterpriseMembership::STATUS_REMOVED)
            ->with(['user', 'primaryTeamMembership.team'])
            ->get();

        if ($memberships->isEmpty()) {
            return 0;
        }

        $byId = $memberships->keyBy('id');
        $byUserId = $memberships->keyBy('user_id');

        $admin = $memberships->firstWhere('role', EnterpriseMembership::ROLE_ADMIN)
            ?? $memberships->first();

        $teamManagers = Team::where('enterprise_id', $enterprise->id)
            ->whereNotNull('manager_id')
            ->pluck('manager_id', 'id');

        $changes = [];

        // Admins are always the roots of the tree.
        foreach ($memberships->where('role', EnterpriseMembership::ROLE_ADMIN) as $adminMember) {
            if ($adminMember->reports_to_membership_id !== null) {
                $changes[] = [$adminMember, null];
            }
        }

        $resolveManager = function (EnterpriseMembership $member) use ($byUserId, $teamManagers, $admin, $byId) {
            $primaryTeamId = $member->primaryTeamMembership?->team_id;

            if ($primaryTeamId && $teamManagers->has($primaryTeamId)) {
                $managerUserId = (int) $teamManagers->get($primaryTeamId);
                $manager = $byUserId->get($managerUserId);

                if ($manager
                    && $manager->id !== $member->id
                    && $manager->status === EnterpriseMembership::STATUS_ACTIVE
                    && !$manager->isAdmin()) {
                    return $manager;
                }
            }

            if ($admin && $admin->id !== $member->id && $admin->status === EnterpriseMembership::STATUS_ACTIVE) {
                return $admin;
            }

            // Fall back to any active, non-admin membership, then any membership.
            $fallback = $byId->first(fn (EnterpriseMembership $candidate) => $candidate->id !== $member->id
                && $candidate->status === EnterpriseMembership::STATUS_ACTIVE
                && !$candidate->isAdmin());

            return $fallback ?? $admin;
        };

        // Employees below a team manager inherit that manager so the tree keeps
        // the existing team structure. Managers report to the admin by default.
        foreach ($memberships->where('role', '!=', EnterpriseMembership::ROLE_ADMIN) as $member) {
            if ($member->reports_to_membership_id !== null) {
                continue;
            }

            $manager = $resolveManager($member);

            if ($manager && $manager->id !== $member->id) {
                $changes[] = [$member, $manager->id];
            }
        }

        if ($dryRun || $changes === []) {
            foreach ($changes as [$member, $managerId]) {
                $this->line(sprintf(
                    '  %s #%d (%s) → %s',
                    $member->role,
                    $member->id,
                    $member->user?->email ?? $member->user_id,
                    $managerId ? ($byId->get($managerId)?->user?->email ?? "#{$managerId}") : 'root'
                ));
            }

            return count($changes);
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as [$member, $managerId]) {
                $member->forceFill(['reports_to_membership_id' => $managerId])->save();
            }
        });

        foreach ($changes as [$member, $managerId]) {
            $this->line(sprintf(
                '  %s #%d (%s) → %s',
                $member->role,
                $member->id,
                $member->user?->email ?? $member->user_id,
                $managerId ? ($byId->get($managerId)?->user?->email ?? "#{$managerId}") : 'root'
            ));
        }

        return count($changes);
    }
}
