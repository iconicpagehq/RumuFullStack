<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CustomerResource;
use App\Models\InventoryUnit;
use App\Models\Lead;
use Illuminate\Http\Request;

class CustomerController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->get('search');
        $statusFilter = $request->get('status');

        $baseQuery = Lead::customers()
            ->forRealtor($user->id)
            ->with(['campaign', 'inventoryUnit.floor.tower.project']);

        $statsQuery = clone $baseQuery;

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'won') {
            $baseQuery->where('status', 'won');
        } elseif ($statusFilter === 'booked') {
            $baseQuery->whereHas('inventoryUnit', fn ($q) => $q->where('availability_status', InventoryUnit::STATUS_BOOKED));
        } elseif ($statusFilter === 'sold') {
            $baseQuery->where(function ($q) {
                $q->where('status', 'won')
                    ->orWhereHas('inventoryUnit', fn ($uq) => $uq->where('availability_status', InventoryUnit::STATUS_SOLD));
            });
        }

        $customers = $baseQuery->recent()->paginate(20);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'won' => (clone $statsQuery)->where('status', 'won')->count(),
            'booked' => (clone $statsQuery)->whereHas('inventoryUnit', fn ($q) => $q->where('availability_status', InventoryUnit::STATUS_BOOKED))->count(),
            'sold' => (clone $statsQuery)->where(function ($q) {
                $q->where('status', 'won')
                    ->orWhereHas('inventoryUnit', fn ($uq) => $uq->where('availability_status', InventoryUnit::STATUS_SOLD));
            })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'customers' => CustomerResource::collection($customers),
            ],
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
            'message' => null,
        ]);
    }
}
