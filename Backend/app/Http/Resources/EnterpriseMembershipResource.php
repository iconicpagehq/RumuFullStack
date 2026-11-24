<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseMembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enterprise_id' => $this->enterprise_id,
            'role' => $this->role,
            'status' => $this->status,
            'designation' => $this->designation,
            'employee_code' => $this->employee_code,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'team' => $this->whenLoaded('primaryTeamMembership', fn () => $this->primaryTeamMembership?->team
                ? new EnterpriseTeamResource($this->primaryTeamMembership->team)
                : null),
            'enterprise' => $this->whenLoaded('enterprise', fn () => new EnterpriseResource($this->enterprise)),
            'inviter' => $this->whenLoaded('inviter', fn () => new UserResource($this->inviter)),
            'reports_to_membership_id' => $this->reports_to_membership_id,
            'reports_to' => $this->whenLoaded('reportsTo', fn () => $this->reportsTo
                ? [
                    'membership_id' => (int) $this->reportsTo->id,
                    'user_id' => (int) $this->reportsTo->user_id,
                    'name' => $this->reportsTo->user?->name,
                    'role' => $this->reportsTo->role,
                    'designation' => $this->reportsTo->designation,
                ]
                : null),
            'direct_reports_count' => $this->direct_reports_count ?? null,
            'subtree_size' => $this->subtree_size ?? null,
            'is_lead' => $this->isLead(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
