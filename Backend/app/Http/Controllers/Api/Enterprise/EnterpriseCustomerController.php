<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseLeadResource;
use App\Models\InventoryUnit;
use App\Models\Lead;
use Illuminate\Http\Request;

class EnterpriseCustomerController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);

        $base = Lead::query()
            ->where('enterprise_id', $membership->enterprise_id)
            ->where(function ($query) {
                $query->where('enterprise_stage', 'won')
                    ->orWhereHas('inventoryUnit', fn ($unit) => $unit->whereIn('availability_status', [
                        InventoryUnit::STATUS_BOOKED,
                        InventoryUnit::STATUS_SOLD,
                    ]));
            });

        if ($membership->isEmployee()) {
            $base->where('assigned_to', $membership->user_id);
        } elseif ($membership->isManager()) {
            $base->where(function ($inner) use ($membership, $request) {
                $inner->whereIn('team_id', $this->teamScopeIds($request))
                    ->orWhere('assigned_manager_id', $membership->user_id);
            });
        }

        $statsQuery = clone $base;

        if ($search = $request->input('search')) {
            $base->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($stage = $request->input('stage')) {
            $base->where('enterprise_stage', $stage);
        }

        if ($request->boolean('booked')) {
            $base->whereHas('inventoryUnit', fn ($unit) => $unit->where('availability_status', 'booked'));
        }

        $customers = $base->with(['assignee', 'team', 'project', 'inventoryUnit.floor.tower.project'])
            ->latest('won_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => EnterpriseLeadResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'stats' => [
                    'total' => (clone $statsQuery)->count(),
                    'won' => (clone $statsQuery)->where('enterprise_stage', 'won')->count(),
                    'booked' => (clone $statsQuery)->whereHas('inventoryUnit', fn ($unit) => $unit->where('availability_status', 'booked'))->count(),
                    'sold' => (clone $statsQuery)->whereHas('inventoryUnit', fn ($unit) => $unit->where('availability_status', 'sold'))->count(),
                    'deal_value' => (float) (clone $statsQuery)->sum('deal_value'),
                ],
            ],
            'message' => null,
        ]);
    }
}
