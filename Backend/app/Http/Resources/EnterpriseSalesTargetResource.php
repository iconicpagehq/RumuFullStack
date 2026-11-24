<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseSalesTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_month' => $this->period_month?->format('Y-m'),
            'metric' => $this->metric,
            'target_value' => (float) $this->target_value,
            'actual_value' => $this->when(isset($this->actual_value), fn () => (float) $this->actual_value),
            'achievement_percentage' => $this->when(
                isset($this->actual_value),
                fn () => $this->target_value > 0
                    ? round(((float) $this->actual_value / (float) $this->target_value) * 100, 1)
                    : null
            ),
            'team' => $this->whenLoaded('team', fn () => new EnterpriseTeamResource($this->team)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
