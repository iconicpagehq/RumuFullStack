<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseMembershipResource;
use App\Http\Resources\EnterpriseResource;
use App\Http\Resources\PlanResource;
use App\Models\Team;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnterpriseSettingsController extends EnterpriseApiController
{
    public function context(Request $request, EnterpriseLimitService $limits)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request)->load('plan');

        return $this->success([
            'enterprise' => new EnterpriseResource($enterprise),
            'membership' => new EnterpriseMembershipResource(
                $membership->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])
            ),
            'hierarchy' => $this->hierarchyPayload($membership),
            'plan' => $enterprise->plan ? new PlanResource($enterprise->plan) : null,
            'features' => [
                'advanced_reports' => $limits->feature($enterprise, 'advanced_reports'),
                'meta_integrations' => $limits->feature($enterprise, 'meta_integrations'),
            ],
        ]);
    }

    public function show(Request $request, EnterpriseLimitService $limits)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request)->load('plan');

        return $this->success([
            'enterprise' => new EnterpriseResource($enterprise),
            'membership' => new EnterpriseMembershipResource(
                $membership->load(['user', 'primaryTeamMembership.team', 'reportsTo.user'])
            ),
            'hierarchy' => $this->hierarchyPayload($membership),
            'plan' => $enterprise->plan ? new PlanResource($enterprise->plan) : null,
            'usage' => $limits->usageSummary($enterprise),
            'features' => [
                'advanced_reports' => $limits->feature($enterprise, 'advanced_reports'),
                'meta_integrations' => $limits->feature($enterprise, 'meta_integrations'),
            ],
        ]);
    }

    /**
     * Viewer-specific hierarchy flags consumed by the enterprise shell.
     */
    private function hierarchyPayload(\App\Models\EnterpriseMembership $membership): array
    {
        $hierarchy = $this->hierarchyService();
        $reportsTo = $membership->reportsTo;
        $hasReports = $hierarchy->hasReports($membership);

        return [
            'can_view_hierarchy' => $membership->isAdmin() || $membership->isManager() || $hasReports,
            'can_manage_hierarchy' => $hierarchy->canManageHierarchy($membership),
            'has_reports' => $hasReports,
            'is_lead_employee' => $hierarchy->isLeadEmployee($membership),
            'scope' => $hierarchy->scopeMode($membership),
            'direct_reports_count' => $hierarchy->directReportsCount($membership),
            'subtree_size' => $hierarchy->subtreeSize($membership),
            'reports_to' => $reportsTo ? [
                'membership_id' => (int) $reportsTo->id,
                'user_id' => (int) $reportsTo->user_id,
                'name' => $reportsTo->user?->name,
                'role' => $reportsTo->role,
                'designation' => $reportsTo->designation,
            ] : null,
        ];
    }

    public function update(Request $request, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('enterprises', 'email')->ignore($enterprise->id),
            ],
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'business_type' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'rera_number' => 'nullable|string|max:100',
            'working_hours' => 'nullable|array',
            'working_hours.*.day' => 'required_with:working_hours|string|max:20',
            'working_hours.*.open' => 'nullable|string|max:10',
            'working_hours.*.close' => 'nullable|string|max:10',
            'working_hours.*.closed' => 'sometimes|boolean',
            'auto_assign_round_robin' => 'sometimes|boolean',
            'lead_duplicate_detection' => 'sometimes|boolean',
            'auto_distribution_mode' => 'sometimes|in:manual,round_robin',
            'auto_distribution_team_id' => 'nullable|integer',
            'billing_interval' => 'sometimes|in:monthly,annual',
        ]);

        if (!empty($validated['auto_distribution_team_id'])) {
            $teamExists = Team::where('enterprise_id', $enterprise->id)
                ->whereKey($validated['auto_distribution_team_id'])
                ->exists();

            if (!$teamExists) {
                throw ValidationException::withMessages([
                    'auto_distribution_team_id' => ['Select a team from this enterprise.'],
                ]);
            }
        }

        $settings = $enterprise->settings ?? [];
        foreach ([
            'auto_assign_round_robin',
            'lead_duplicate_detection',
            'auto_distribution_mode',
            'auto_distribution_team_id',
            'billing_interval',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $settings[$key] = $validated[$key];
                unset($validated[$key]);
            }
        }

        $enterprise->update([...$validated, 'settings' => $settings]);

        $audit->log($enterprise, $request->user(), 'enterprise.settings_updated', $enterprise);

        return $this->success(
            new EnterpriseResource($enterprise->fresh()->load('plan')),
            'Enterprise settings updated.'
        );
    }

    public function uploadLogo(Request $request, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $enterprise = $this->enterprise($request);

        if ($enterprise->logo_path) {
            Storage::disk('public')->delete($enterprise->logo_path);
        }

        $path = $request->file('logo')->store('enterprise-logos', 'public');
        $enterprise->update(['logo_path' => $path]);

        $audit->log($enterprise, $request->user(), 'enterprise.logo_updated', $enterprise);

        return $this->success(
            new EnterpriseResource($enterprise->fresh()->load('plan')),
            'Company logo updated.'
        );
    }

    public function removeLogo(Request $request, EnterpriseAuditService $audit)
    {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        if ($enterprise->logo_path) {
            Storage::disk('public')->delete($enterprise->logo_path);
            $enterprise->update(['logo_path' => null]);
        }

        $audit->log($enterprise, $request->user(), 'enterprise.logo_removed', $enterprise);

        return $this->success(
            new EnterpriseResource($enterprise->fresh()->load('plan')),
            'Company logo removed.'
        );
    }

    public function updateProfile(Request $request, EnterpriseAuditService $audit)
    {
        $membership = $this->membership($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'designation' => 'nullable|string|max:120',
        ]);

        $membership->user->update(collect($validated)->only(['name', 'phone'])->all());

        if (!$membership->isAdmin() && array_key_exists('designation', $validated)) {
            $membership->update(['designation' => $validated['designation']]);
        }

        $audit->log($this->enterprise($request), $request->user(), 'profile.updated', $request->user());

        return $this->success([
            'membership' => new EnterpriseMembershipResource(
                $membership->fresh()->load(['user', 'primaryTeamMembership.team'])
            ),
        ], 'Profile updated.');
    }

    public function updatePassword(Request $request, EnterpriseAuditService $audit)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        $audit->log($this->enterprise($request), $request->user(), 'profile.password_updated', $request->user());

        return $this->success(null, 'Password updated.');
    }
}
