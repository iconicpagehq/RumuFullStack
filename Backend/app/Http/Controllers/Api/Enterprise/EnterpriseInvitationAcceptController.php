<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\EnterpriseMembershipResource;
use App\Http\Resources\UserResource;
use App\Models\EnterpriseInvitation;
use App\Models\EnterpriseMembership;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseHierarchyService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnterpriseInvitationAcceptController extends ApiController
{
    public function show(string $token)
    {
        $invitation = $this->findInvitation($token);

        return $this->success([
            'invitation' => new \App\Http\Resources\EnterpriseInvitationResource(
                $invitation->load(['enterprise', 'team', 'inviter', 'reportsTo.user'])
            ),
            'enterprise' => [
                'name' => $invitation->enterprise?->name,
                'slug' => $invitation->enterprise?->slug,
            ],
            'account_exists' => User::where('email', $invitation->email)->exists(),
        ]);
    }

    public function accept(
        Request $request,
        string $token,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $invitation = $this->findInvitation($token);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
        ]);

        if (User::where('email', $invitation->email)->exists()) {
            return $this->error(
                'An account with this email already exists. Enterprise invitations require a new enterprise account.',
                422
            );
        }

        $result = DB::transaction(function () use ($invitation, $validated, $audit, $limits) {
            $invitation = EnterpriseInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if (!$invitation->isPending()) {
                return null;
            }

            $enterprise = $invitation->enterprise;
            $resource = $invitation->role === EnterpriseMembership::ROLE_MANAGER ? 'managers' : 'employees';
            $limits->ensure($enterprise, $resource, 0);

            $hierarchy = app(EnterpriseHierarchyService::class);

            $manager = null;
            if ($invitation->reports_to_membership_id) {
                $manager = EnterpriseMembership::where('enterprise_id', $enterprise->id)
                    ->whereKey($invitation->reports_to_membership_id)
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->first();
            }

            // The invited manager may have left or been removed since the
            // invitation was sent. Fall back to an active company admin so the
            // new member never lands outside the reporting tree.
            $manager ??= $hierarchy->defaultAdmin($enterprise);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'enterprise',
                'plan_id' => $enterprise->plan_id,
                'email_verified_at' => now(),
            ]);

            EnterpriseMembership::create([
                'enterprise_id' => $enterprise->id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'status' => EnterpriseMembership::STATUS_ACTIVE,
                'joined_at' => now(),
                'invited_by' => $invitation->invited_by,
                'reports_to_membership_id' => $manager?->id,
            ]);

            $hierarchy->flush($enterprise->id);

            if ($invitation->team_id) {
                $team = Team::where('enterprise_id', $enterprise->id)->find($invitation->team_id);
                if ($team) {
                    TeamMembership::create([
                        'enterprise_id' => $enterprise->id,
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'is_primary' => true,
                        'joined_at' => now(),
                    ]);
                }
            }

            $invitation->update([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);

            $audit->log($enterprise, $user, 'member.invitation_accepted', $user, 'Invitation accepted', [
                'role' => $invitation->role,
                'team_id' => $invitation->team_id,
                'reports_to_membership_id' => $manager?->id,
            ]);

            return [$user, $enterprise];
        });

        if ($result === null) {
            return $this->error('This invitation is no longer available.', 410);
        }

        [$user, $enterprise] = $result;

        $token = $user->createToken('enterprise-spa')->plainTextToken;

        $user->load([
            'plan',
            'enterpriseMembership.enterprise.plan',
            'enterpriseMembership.primaryTeamMembership.team',
            'enterpriseMembership.reportsTo.user',
        ]);

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'membership' => new EnterpriseMembershipResource($user->enterpriseMembership),
        ], 'Invitation accepted.', 201);
    }

    private function findInvitation(string $token): EnterpriseInvitation
    {
        $invitation = EnterpriseInvitation::with(['enterprise', 'team', 'inviter'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_if(!$invitation, 404, 'Invitation not found.');
        abort_if($invitation->revoked_at !== null, 410, 'This invitation has been revoked.');
        abort_if($invitation->accepted_at !== null, 410, 'This invitation has already been used.');
        abort_if($invitation->isExpired(), 410, 'This invitation has expired.');
        abort_if($invitation->enterprise?->status !== 'active', 410, 'This enterprise workspace is unavailable.');

        return $invitation;
    }
}
