<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user, Campaign $campaign): bool
    {
        if (!$this->isRealtorCampaign($user, $campaign)) {
            return false;
        }

        return $user->role === 'admin' || $campaign->user_id === $user->id;
    }

    public function view(User $user, Lead $lead): bool
    {
        if (!$this->isRealtorLead($user, $lead)) {
            return false;
        }

        return $user->role === 'admin' || $lead->campaign?->user_id === $user->id;
    }

    public function create(User $user, Campaign $campaign): bool
    {
        if (!$this->isRealtorCampaign($user, $campaign)) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($campaign->user_id !== $user->id) {
            return false;
        }

        return $user->canAddLeadToCampaign($campaign);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->role === 'admin' && $this->isRealtorLead($user, $lead);
    }

    public function addNote(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    public function updateStatus(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    private function isRealtorCampaign(User $user, Campaign $campaign): bool
    {
        return in_array($user->role, ['admin', 'realtor'], true)
            && $campaign->enterprise_id === null;
    }

    private function isRealtorLead(User $user, Lead $lead): bool
    {
        return in_array($user->role, ['admin', 'realtor'], true)
            && $lead->enterprise_id === null;
    }
}
