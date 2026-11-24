<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'audience' => $this->audience,
            'max_campaigns' => $this->max_campaigns,
            'max_leads_per_campaign' => $this->max_leads_per_campaign,
            'max_managers' => $this->max_managers,
            'max_employees' => $this->max_employees,
            'max_teams' => $this->max_teams,
            'max_monthly_leads' => $this->max_monthly_leads,
            'max_inventory_units' => $this->max_inventory_units,
            'advanced_reports' => (bool) $this->advanced_reports,
            'meta_integrations' => (bool) $this->meta_integrations,
            'price' => $this->price,
            'sort_order' => $this->sort_order,
            'is_default' => (bool) $this->is_default,
            'is_active' => $this->is_active,
            'header_color' => $this->header_color,
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
