<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class EnterpriseLeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'status' => $this->status,
            'stage' => $this->enterprise_stage ?: 'new',
            'stage_label' => Str::headline($this->enterprise_stage ?: 'new'),
            'priority' => $this->priority,
            'source' => $this->source,
            'budget_min' => $this->budget_min,
            'budget_max' => $this->budget_max,
            'preferred_location' => $this->preferred_location,
            'preferred_configuration' => $this->preferred_configuration,
            'next_follow_up_at' => $this->next_follow_up_at?->toIso8601String(),
            'last_contacted_at' => $this->last_contacted_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'deal_value' => $this->deal_value,
            'lost_reason' => $this->lost_reason,
            'won_at' => $this->won_at?->toIso8601String(),
            'custom_fields' => $this->custom_fields ?? [],
            'enterprise_id' => $this->enterprise_id,
            'team_id' => $this->team_id,
            'assigned_to' => $this->assigned_to,
            'assigned_manager_id' => $this->assigned_manager_id,
            'inventory_project_id' => $this->inventory_project_id,
            'inventory_unit_id' => $this->inventory_unit_id,
            'campaign_id' => $this->campaign_id,
            'assignee' => $this->whenLoaded('assignee', fn () => new UserResource($this->assignee)),
            'assigned_manager' => $this->whenLoaded('assignedManager', fn () => new UserResource($this->assignedManager)),
            'team' => $this->whenLoaded('team', fn () => new EnterpriseTeamResource($this->team)),
            'project' => $this->whenLoaded('project', fn () => new EnterpriseProjectResource($this->project)),
            'campaign' => $this->whenLoaded('campaign', fn () => new CampaignResource($this->campaign)),
            'unit' => $this->whenLoaded('inventoryUnit', fn () => new InventoryUnitResource($this->inventoryUnit)),
            'activities' => $this->whenLoaded('activities', fn () => LeadActivityResource::collection($this->activities)),
            'tasks' => $this->whenLoaded('tasks', fn () => EnterpriseTaskResource::collection($this->tasks)),
            'site_visits' => $this->whenLoaded('siteVisits', fn () => EnterpriseSiteVisitResource::collection($this->siteVisits)),
            'activities_count' => $this->whenCounted('activities'),
            'open_tasks_count' => $this->when(isset($this->open_tasks_count), fn () => (int) $this->open_tasks_count),
            'is_overdue' => $this->next_follow_up_at !== null
                && $this->next_follow_up_at->isPast()
                && !in_array($this->enterprise_stage, ['won', 'lost'], true),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
