<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReferralController extends ApiController
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('referrals')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => (int) $request->input('per_page', 20),
                    'total' => 0,
                ],
                'stats' => [
                    'total' => 0,
                    'completed' => 0,
                    'pending' => 0,
                    'total_rewards' => 0,
                ],
                'message' => null,
            ]);
        }

        $query = Referral::with(['referrer', 'referred']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('referrer', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('referred', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $referrals = $query->latest()->paginate((int) $request->input('per_page', 20));

        $rows = $referrals->getCollection()->map(fn (Referral $referral) => [
            'id' => $referral->id,
            'status' => $referral->status,
            'reward_amount' => $referral->reward_amount,
            'referrer' => (new UserResource($referral->referrer))->resolve(),
            'referred' => (new UserResource($referral->referred))->resolve(),
            'created_at' => $referral->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'current_page' => $referrals->currentPage(),
                'last_page' => $referrals->lastPage(),
                'per_page' => $referrals->perPage(),
                'total' => $referrals->total(),
            ],
            'stats' => [
                'total' => Referral::count(),
                'completed' => Referral::where('status', 'completed')->count(),
                'pending' => Referral::where('status', 'pending')->count(),
                'total_rewards' => (float) Referral::sum('reward_amount'),
            ],
            'message' => null,
        ]);
    }
}
