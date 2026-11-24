<?php

namespace App\Services;

use App\Models\InventoryProject;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\User;
use InvalidArgumentException;

class LeadInventorySyncService
{
    public function __construct(
        private InventoryAvailabilityService $availabilityService
    ) {}

    public function sync(Lead $lead, ?int $unitId, ?int $previousUnitId, User $actor): void
    {
        if ($previousUnitId && $previousUnitId !== $unitId) {
            $previous = InventoryUnit::find($previousUnitId);
            if ($previous) {
                $this->availabilityService->releaseUnit($previous, $actor, 'Unlinked from lead');
            }
        }

        if (!$unitId) {
            return;
        }

        $unit = InventoryUnit::with('floor.tower.project')->findOrFail($unitId);

        if ($unit->floor->tower->project->user_id !== $lead->campaign->user_id) {
            throw new InvalidArgumentException('Selected unit does not belong to your inventory.');
        }

        $this->availabilityService->assignToLead($unit, $lead, $actor);
        $this->availabilityService->syncFromLeadStatus($lead->fresh(), $actor);
    }

    public function mergeProjectNameFromUnit(?array $customFields, ?int $unitId): ?array
    {
        $customFields = $customFields ?? [];

        if (!$unitId) {
            return $customFields ?: null;
        }

        $unit = InventoryUnit::with('floor.tower.project')->find($unitId);
        if ($unit?->floor?->tower?->project) {
            $customFields['project_name'] = $unit->floor->tower->project->name;
        }

        return $customFields ?: null;
    }

    public function isValidPhone(string $phone): bool
    {
        $pattern = '/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/';

        return preg_match($pattern, $phone) === 1;
    }

    public function leadValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!empty($value) && !$this->isValidPhone($value)) {
                    $fail('The phone number format is invalid.');
                }
            }],
            'company' => 'nullable|string|max:255',
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'note' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_fields.client_type' => 'nullable|in:buyer,seller,investor,tenant',
            'custom_fields.budget_range' => 'nullable|in:below_50l,50l_75l,75l_1cr,1cr_1.5cr,1.5cr_2cr,2cr_3cr,3cr_5cr,above_5cr',
            'custom_fields.purpose' => 'nullable|in:self_use,investment,rental',
            'custom_fields.family_size' => 'nullable|in:1,2,3,4,5,5+',
            'custom_fields.property_type' => 'nullable|in:apartment,villa,plot,commercial,office,shop',
            'custom_fields.configuration' => 'nullable|in:studio,1_bhk,2_bhk,3_bhk,4_bhk,4+_bhk',
            'custom_fields.preferred_location' => 'nullable|string|max:255',
            'custom_fields.project_name' => 'nullable|string|max:255',
            'custom_fields.construction_status' => 'nullable|in:ready_to_move,under_construction,both',
            'custom_fields.city' => 'nullable|string|max:255',
            'custom_fields.site_visit_required' => 'nullable|in:yes,no',
            'custom_fields.preferred_visit_date' => 'nullable|date',
            'custom_fields.landmarks' => 'nullable|string|max:500',
            'custom_fields.lead_source' => 'nullable|in:facebook,google,portal,walk_in,referral,other',
            'custom_fields.lead_priority' => 'nullable|in:hot,warm,cold',
            'custom_fields.buying_timeline' => 'nullable|in:immediate,1_3_months,3_6_months,6_12_months,just_browsing',
            'custom_fields.funding_type' => 'nullable|in:self,loan,bank_approved',
            'inventory_unit_id' => 'nullable|exists:inventory_units,id',
        ];
    }
}
