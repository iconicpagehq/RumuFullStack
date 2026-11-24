<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_at' => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completion_notes' => $this->completion_notes,
            'is_overdue' => $this->isOverdue(),
            'lead' => $this->whenLoaded('lead', fn () => new EnterpriseLeadResource($this->lead)),
            'team' => $this->whenLoaded('team', fn () => new EnterpriseTeamResource($this->team)),
            'assignee' => $this->whenLoaded('assignee', fn () => new UserResource($this->assignee)),
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
