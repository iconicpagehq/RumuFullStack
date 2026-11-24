<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Services\PlanExpiryService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class RealtorController extends Controller
{
    /**
     * Show Realtor dashboard
     */
    public function dashboard(PlanExpiryService $planExpiry)
    {
        $user = auth()->user();
        
        $campaigns = $user->campaigns()->with('leads')->get();
        
        $campaignStatusData = [
            'active' => $campaigns->where('status', 'active')->count(),
            'paused' => $campaigns->where('status', 'paused')->count(),
            'completed' => $campaigns->where('status', 'completed')->count(),
        ];
        
        $allLeads = $campaigns->flatMap->leads;
        $leadStatusData = [
            'new' => $allLeads->where('status', 'new')->count(),
            'contacted' => $allLeads->where('status', 'contacted')->count(),
            'qualified' => $allLeads->where('status', 'qualified')->count(),
            'proposal' => $allLeads->where('status', 'proposal')->count(),
            'negotiation' => $allLeads->where('status', 'negotiation')->count(),
            'won' => $allLeads->where('status', 'won')->count(),
            'lost' => $allLeads->where('status', 'lost')->count(),
        ];
        
        $months = [];
        $monthlyLeads = [];
        $monthlyCampaigns = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');
            
            $monthlyLeads[] = $allLeads->filter(function ($lead) use ($date) {
                return $lead->created_at->format('Y-m') === $date->format('Y-m');
            })->count();
            
            $monthlyCampaigns[] = $campaigns->filter(function ($campaign) use ($date) {
                return $campaign->created_at->format('Y-m') === $date->format('Y-m');
            })->count();
        }
        
        $recentCampaigns = $campaigns->sortByDesc('created_at')->take(5);
        $planExpiryWarning = $planExpiry->getExpiryWarning($user);
        
        return view('realtor.dashboard', compact(
            'campaignStatusData',
            'leadStatusData',
            'months',
            'monthlyLeads',
            'monthlyCampaigns',
            'recentCampaigns',
            'planExpiryWarning'
        ));
    }

    /**
     * Show plans/pricing page
     */
    public function plans(PlanExpiryService $planExpiry)
    {
        $user = auth()->user();
        $plans = Plan::active()->ordered()->get();
        $currentPlan = $user->plan;
        $userCampaignsCount = $user->campaigns()->count();
        
        // Check for pending upgrade request
        $pendingRequest = PlanUpgradeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();
        
        $expiryInfo = null;
        $expiresAt = $planExpiry->resolveApprovedExpiry($user);

        if ($expiresAt?->isPast() && $currentPlan && $currentPlan->key === 'pro') {
            $basicPlan = Plan::where('key', 'basic')->first();
            if ($basicPlan) {
                $user->plan_id = $basicPlan->id;
                $user->save();
                $currentPlan = $basicPlan;

                $approvedRequest = PlanUpgradeRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->latest('approved_at')
                    ->first();

                $approvedRequest?->update([
                    'admin_notes' => 'Plan expired after 30 days and reverted to Basic',
                ]);

                return redirect()->route('realtor.plans')
                    ->with('warning', 'Your Pro plan has expired and has been reverted to Basic. Please upgrade again if needed.');
            }
        }

        $expiryInfo = $planExpiry->getExpiryInfo($user);
        
        return view('realtor.plans', compact('plans', 'currentPlan', 'userCampaignsCount', 'pendingRequest', 'expiryInfo'));
    }

    /**
     * Request plan upgrade
     */
    public function requestUpgrade(Request $request)
    {
        $user = auth()->user();
        
        // Check if already has a pending request
        $existingRequest = PlanUpgradeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();
            
        if ($existingRequest) {
            return redirect()->back()->with('info', 'You already have a pending upgrade request. Please wait for admin approval.');
        }
        
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $targetPlan = Plan::active()->find($validated['plan_id']);

        if (!$targetPlan) {
            return redirect()->back()->with('error', 'That plan is not available. Please choose another plan or contact support.');
        }

        $currentPlan = $user->plan;

        if (!$targetPlan->isUpgradeFrom($currentPlan)) {
            return redirect()->back()->with('error', 'You cannot upgrade to that plan from your current plan.');
        }

        PlanUpgradeRequest::create([
            'user_id' => $user->id,
            'current_plan_id' => $user->plan_id ?? Plan::where('key', 'basic')->first()?->id,
            'requested_plan_id' => $targetPlan->id,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', "Upgrade request for {$targetPlan->name} sent to admin! You will be notified once approved.");
    }

    /**
     * Show profile page
     */
    public function profile()
    {
        $user = auth()->user();
        $stats = [
            'total_campaigns' => $user->campaigns()->count(),
            'total_leads' => $user->campaigns()->withCount('leads')->get()->sum('leads_count'),
            'active_campaigns' => $user->campaigns()->where('status', 'active')->count(),
            'won_leads' => $user->campaigns()->withCount(['leads' => function ($query) {
                $query->where('status', 'won');
            }])->get()->sum('leads_count'),
        ];
        
        return view('realtor.profile', compact('user', 'stats'));
    }

    /**
     * Update profile information
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $user->update($validated);

        return redirect()->route('realtor.profile')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password'])
        ]);

        return redirect()->route('realtor.profile')
            ->with('success', 'Password updated successfully!');
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        $user = auth()->user();
        
        $timezones = [
            'Asia/Kolkata' => 'India (IST)',
            'America/New_York' => 'New York (EST)',
            'America/Chicago' => 'Chicago (CST)',
            'America/Denver' => 'Denver (MST)',
            'America/Los_Angeles' => 'Los Angeles (PST)',
            'Europe/London' => 'London (GMT)',
            'Europe/Paris' => 'Paris (CET)',
            'Europe/Berlin' => 'Berlin (CET)',
            'Asia/Dubai' => 'Dubai (GST)',
            'Asia/Singapore' => 'Singapore (SGT)',
            'Asia/Tokyo' => 'Tokyo (JST)',
            'Australia/Sydney' => 'Sydney (AEST)',
        ];
        
        $languages = [
            'en' => 'English',
            'hi' => 'Hindi',
            'mr' => 'Marathi',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
        ];
        
        return view('realtor.settings', compact('user', 'timezones', 'languages'));
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['boolean'],
            'marketing_emails' => ['boolean'],
        ]);

        auth()->user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ]);

        return redirect()->route('realtor.settings')
            ->with('success', 'Notification settings updated!');
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacy(Request $request)
    {
        $validated = $request->validate([
            'public_profile' => ['boolean'],
        ]);

        auth()->user()->update([
            'public_profile' => $request->boolean('public_profile'),
        ]);

        return redirect()->route('realtor.settings')
            ->with('success', 'Privacy settings updated!');
    }

    /**
     * Update appearance settings
     */
    public function updateAppearance(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark,auto'],
            'language' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'string', 'max:50'],
        ]);

        auth()->user()->update($validated);

        return redirect()->route('realtor.settings')
            ->with('success', 'Appearance settings updated!');
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $user = auth()->user();
        
        // Logout the user
        auth()->logout();
        
        // Delete user avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        // Delete the user (soft delete if using SoftDeletes)
        $user->delete();

        return redirect()->route('login')
            ->with('success', 'Your account has been deleted. We\'re sorry to see you go!');
    }
}
