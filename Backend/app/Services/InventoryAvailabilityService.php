<?php

namespace App\Services;

use App\Models\InventoryUnit;
use App\Models\InventoryUnitStatusLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryAvailabilityService
{
    public function assignToLead(InventoryUnit $unit, Lead $lead, User $actor): void
    {
        if ($unit->availability_status === InventoryUnit::STATUS_SOLD) {
            throw new InvalidArgumentException('Cannot assign a sold unit to a lead.');
        }

        $existingLead = Lead::query()
            ->where('inventory_unit_id', $unit->id)
            ->where('id', '!=', $lead->id)
            ->whereNotIn('status', ['lost'])
            ->exists();

        if ($existingLead) {
            throw new InvalidArgumentException('This unit is already assigned to another active lead.');
        }

        DB::transaction(function () use ($unit, $lead, $actor) {
            if ($lead->inventory_unit_id && $lead->inventory_unit_id !== $unit->id) {
                $previous = InventoryUnit::find($lead->inventory_unit_id);
                if ($previous) {
                    $this->releaseUnit($previous, $actor, 'Reassigned to another unit');
                }
            }

            $targetStatus = $this->statusForLead($lead);

            $this->transition($unit, $targetStatus, $actor, $lead, 'Assigned to lead');

            $unit->lead_id = $lead->id;
            $unit->save();

            $lead->inventory_unit_id = $unit->id;
            $lead->save();
        });
    }

    public function syncFromLeadStatus(Lead $lead, User $actor): void
    {
        if (!$lead->inventory_unit_id) {
            return;
        }

        $unit = InventoryUnit::find($lead->inventory_unit_id);
        if (!$unit) {
            return;
        }

        if ($lead->status === 'won') {
            $this->transition($unit, InventoryUnit::STATUS_SOLD, $actor, $lead, 'Lead marked as won');
            return;
        }

        if ($lead->status === 'lost') {
            $this->releaseUnit($unit, $actor, 'Lead marked as lost');
            $lead->inventory_unit_id = null;
            $lead->save();
            return;
        }

        if (in_array($lead->status, ['negotiation', 'proposal'], true)) {
            $this->transition($unit, InventoryUnit::STATUS_BOOKED, $actor, $lead, 'Lead advanced to ' . $lead->status);
            return;
        }

        if ($unit->availability_status !== InventoryUnit::STATUS_SOLD) {
            $this->transition($unit, InventoryUnit::STATUS_HOLD, $actor, $lead, 'Lead status synced');
        }
    }

    public function releaseUnit(InventoryUnit $unit, User $actor, ?string $note = null): void
    {
        if ($unit->availability_status === InventoryUnit::STATUS_SOLD) {
            return;
        }

        DB::transaction(function () use ($unit, $actor, $note) {
            $this->transition($unit, InventoryUnit::STATUS_AVAILABLE, $actor, null, $note ?? 'Unit released');

            $unit->lead_id = null;
            $unit->save();

            Lead::query()
                ->where('inventory_unit_id', $unit->id)
                ->update(['inventory_unit_id' => null]);
        });
    }

    public function updateStatus(InventoryUnit $unit, string $status, User $actor, ?string $note = null): void
    {
        if (!in_array($status, InventoryUnit::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid availability status.');
        }

        $this->transition($unit, $status, $actor, $unit->lead, $note);

        if ($status === InventoryUnit::STATUS_AVAILABLE) {
            $unit->lead_id = null;
            $unit->save();
        }
    }

    private function statusForLead(Lead $lead): string
    {
        return match ($lead->status) {
            'won' => InventoryUnit::STATUS_SOLD,
            'negotiation', 'proposal' => InventoryUnit::STATUS_BOOKED,
            default => InventoryUnit::STATUS_HOLD,
        };
    }

    private function transition(
        InventoryUnit $unit,
        string $newStatus,
        User $actor,
        ?Lead $lead,
        ?string $note
    ): void {
        if ($unit->availability_status === $newStatus) {
            return;
        }

        InventoryUnitStatusLog::create([
            'inventory_unit_id' => $unit->id,
            'old_status' => $unit->availability_status,
            'new_status' => $newStatus,
            'changed_by' => $actor->id,
            'lead_id' => $lead?->id,
            'note' => $note,
            'created_at' => now(),
        ]);

        $unit->availability_status = $newStatus;
        $unit->save();
    }
}
