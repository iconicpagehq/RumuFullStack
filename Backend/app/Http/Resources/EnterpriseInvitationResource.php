<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->revoked_at
            ? 'revoked'
            : ($this->accepted_at ? 'accepted' : ($this->isExpired() ? 'expired' : 'pending'));

        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'team' => $this->whenLoaded('team', fn () => new EnterpriseTeamResource($this->team)),
            'invited_by' => $this->whenLoaded('inviter', fn () => new UserResource($this->inviter)),
            'reports_to_membership_id' => $this->reports_to_membership_id,
            'reports_to' => $this->whenLoaded('reportsTo', fn () => $this->reportsTo
                ? [
                    'membership_id' => (int) $this->reportsTo->id,
                    'user_id' => (int) $this->reportsTo->user_id,
                    'name' => $this->reportsTo->user?->name,
                    'role' => $this->reportsTo->role,
                ]
                : null),
            'invite_url' => $this->when(isset($this->invite_url), fn () => $this->invite_url),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
