<?php

namespace App\Services\Calls;

use App\Models\CallLog;
use App\Models\EnterpriseMembership;
use App\Services\EnterpriseHierarchyService;
use Illuminate\Database\Eloquent\Builder;

class CallAccessService
{
    public function __construct(private EnterpriseHierarchyService $hierarchy)
    {
    }

    public function realtorQuery(int $userId): Builder
    {
        return CallLog::query()->forRealtor($userId);
    }

    public function enterpriseQuery(EnterpriseMembership $membership): Builder
    {
        $query = CallLog::query()->forEnterprise((int) $membership->enterprise_id);

        if ($membership->isAdmin()) {
            return $query;
        }

        $membershipIds = $this->hierarchy->scopeIds($membership);
        $userIds = $this->hierarchy->scopeUserIds($membership);
        $teamIds = $membership->isManager() ? $this->hierarchy->managedTeamIds($membership) : [];

        return $query->where(function (Builder $inner) use ($membershipIds, $userIds, $teamIds) {
            $inner->whereIn('enterprise_membership_id', $membershipIds)
                ->orWhereIn('initiated_by', $userIds);

            if ($teamIds) {
                $inner->orWhereIn('team_id', $teamIds);
            }
        });
    }

    public function visibleToRealtor(CallLog $call, int $userId): bool
    {
        return $call->edition === CallLog::EDITION_REALTOR
            && (int) $call->realtor_user_id === $userId;
    }

    public function visibleToEnterprise(CallLog $call, EnterpriseMembership $membership): bool
    {
        if ($call->edition !== CallLog::EDITION_ENTERPRISE
            || (int) $call->enterprise_id !== (int) $membership->enterprise_id) {
            return false;
        }

        if ($membership->isAdmin()) {
            return true;
        }

        if (in_array((int) $call->enterprise_membership_id, $this->hierarchy->scopeIds($membership), true)) {
            return true;
        }

        if (in_array((int) $call->initiated_by, $this->hierarchy->scopeUserIds($membership), true)) {
            return true;
        }

        return $membership->isManager()
            && in_array((int) $call->team_id, $this->hierarchy->managedTeamIds($membership), true);
    }

    public function canDeleteRecordingRealtor(CallLog $call, int $userId): bool
    {
        return $this->visibleToRealtor($call, $userId);
    }

    public function canDeleteRecordingEnterprise(CallLog $call, EnterpriseMembership $membership): bool
    {
        if (!$this->visibleToEnterprise($call, $membership)) {
            return false;
        }

        return $membership->isAdmin() || (int) $call->initiated_by === (int) $membership->user_id;
    }
}
