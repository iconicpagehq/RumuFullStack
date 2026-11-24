<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadsController extends ApiController
{
    public function index(Request $request)
    {
        $tenant = $request->input('tenant', 'all');
        $query = Lead::with(['campaign', 'campaign.user', 'enterprise:id,name']);

        if ($tenant === 'realtor') {
            $query->whereNull('enterprise_id');
        } elseif ($tenant === 'enterprise') {
            $query->whereNotNull('enterprise_id');
        }

        if ($enterpriseId = $request->input('enterprise_id')) {
            $query->where('enterprise_id', $enterpriseId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($campaignId = $request->input('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        return $this->paginated(LeadResource::collection(
            $query->latest()->paginate((int) $request->input('per_page', 20))
        ));
    }
}
