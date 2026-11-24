<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            // Admin can see all campaigns or their own
            $campaigns = Campaign::with('user', 'leads')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        } else {
            // Realtors see only their campaigns
            $campaigns = $user->campaigns()->with('leads')->latest()->get();
        }

        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new campaign
     */
    public function create()
    {
        $user = Auth::user();

        // Check if user can create more campaigns
        if (!$user->canCreateCampaign()) {
            return redirect()->route('campaigns.index')
                ->with('error', 'You have reached your campaign limit. Please upgrade to Pro plan.');
        }

        return view('campaigns.create');
    }

    /**
     * Store a newly created campaign
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate plan limits
        if (!$user->canCreateCampaign()) {
            return redirect()->route('campaigns.index')
                ->with('error', 'You have reached your campaign limit. Please upgrade to Pro plan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,paused,completed',
        ]);

        $campaign = $user->campaigns()->create($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully!');
    }

    /**
     * Display the specified campaign
     */
    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load('leads.notes');

        return view('campaigns.show', compact('campaign'));
    }

    /**
     * Show the form for editing the campaign
     */
    public function edit(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        return view('campaigns.edit', compact('campaign'));
    }

    /**
     * Update the specified campaign
     */
    public function update(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,paused,completed',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully!');
    }

    /**
     * Remove the specified campaign
     */
    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully!');
    }
}
