<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends ApiController
{
    public function index(Request $request)
    {
        $campaigns = $request->user()
            ->campaigns()
            ->withCount('leads')
            ->latest()
            ->get();

        return $this->success(CampaignResource::collection($campaigns));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateCampaign()) {
            return $this->error('You have reached your campaign limit. Please upgrade your plan.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,paused,completed',
        ]);

        $campaign = $user->campaigns()->create($validated);

        return $this->success(new CampaignResource($campaign), 'Campaign created.', 201);
    }

    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load(['leads' => fn ($q) => $q->latest()]);

        return $this->success(new CampaignResource($campaign));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,paused,completed',
        ]);

        $campaign->update($validated);

        return $this->success(new CampaignResource($campaign), 'Campaign updated.');
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return $this->success(null, 'Campaign deleted.');
    }
}
