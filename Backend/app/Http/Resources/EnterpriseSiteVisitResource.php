<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseSiteVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status,
            'customer_attended' => $this->customer_attended,
            'outcome' => $this->outcome,
            'feedback' => $this->feedback,
            'next_action' => $this->next_action,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'lead' => $this->whenLoaded('lead', fn () => new EnterpriseLeadResource($this->lead)),
            'project' => $this->whenLoaded('project', fn () => new EnterpriseProjectResource($this->project)),
            'unit' => $this->whenLoaded('unit', fn () => new InventoryUnitResource($this->unit)),
            'team' => $this->whenLoaded('team', fn () => new EnterpriseTeamResource($this->team)),
            'assignee' => $this->whenLoaded('assignee', fn () => new UserResource($this->assignee)),
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
