<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseInvitationResource;
use App\Models\EnterpriseInvitation;
use App\Models\EnterpriseMembership;
use App\Models\Team;
use App\Models\User;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnterpriseInvitationController extends EnterpriseApiController
{
    public function index(Request $request, EnterpriseLimitService $limits)
    {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $query = EnterpriseInvitation::where('enterprise_id', $enterprise->id)
            ->with(['team', 'inviter', 'reportsTo.user'])
            ->latest();

        if ($status = $request->input('status')) {
            match ($status) {
                'pending' => $query->pending()->where('expires_at', '>', now()),
                'accepted' => $query->whereNotNull('accepted_at'),
                'revoked' => $query->whereNotNull('revoked_at'),
                'expired' => $query->pending()->where('expires_at', '<=', now()),
                default => null,
            };
        }

        if ($search = $request->input('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        return $this->paginated(EnterpriseInvitationResource::collection(
            $query->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function store(
        Request $request,
        EnterpriseAuditService $audit,
        EnterpriseLimitService $limits,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:manager,employee',
            'team_id' => 'nullable|integer',
            'reports_to_membership_id' => 'nullable|integer',
        ]);

        if (EnterpriseInvitation::where('enterprise_id', $enterprise->id)
            ->pending()
            ->where('email', strtolower($validated['email']))
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => ['A pending invitation already exists for this email.'],
            ]);
        }

        $team = null;
        if (!empty($validated['team_id'])) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
        }

        $limits->ensure($enterprise, $validated['role'] === 'manager' ? 'managers' : 'employees');

        $manager = null;
        if (!empty($validated['reports_to_membership_id'])) {
            $manager = EnterpriseMembership::where('enterprise_id', $enterprise->id)
                ->find($validated['reports_to_membership_id']);
        }

        $manager ??= $this->membership($request);
        $this->hierarchyService()->assertSelectableManager($enterprise, $manager);

        [$invitation, $rawToken] = $this->createInvitation(
            $enterprise->id,
            strtolower($validated['email']),
            $validated['role'],
            $team?->id,
            $manager->id,
            $request->user()->id
        );

        $inviteUrl = $this->inviteUrl($request, $rawToken);
        $invitation->invite_url = $inviteUrl;

        $audit->log($enterprise, $request->user(), 'member.invited', $invitation, 'Team member invited', [
            'email' => $invitation->email,
            'role' => $invitation->role,
            'team_id' => $invitation->team_id,
            'reports_to_membership_id' => $manager->id,
        ]);

        $this->sendInvitationEmail($invitation, $inviteUrl);

        $invitation->load(['team', 'inviter', 'reportsTo.user']);

        return $this->success(new EnterpriseInvitationResource($invitation), 'Invitation created.', 201);
    }

    public function resend(
        Request $request,
        EnterpriseInvitation $invitation,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureInvitationBelongs($request, $invitation);

        if (!$invitation->isPending()) {
            return $this->error('Only pending invitations can be resent.', 422);
        }

        $rawToken = Str::random(64);
        $invitation->update([
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = $this->inviteUrl($request, $rawToken);
        $invitation->invite_url = $inviteUrl;

        $audit->log($this->enterprise($request), $request->user(), 'member.invitation_resent', $invitation);

        $this->sendInvitationEmail($invitation, $inviteUrl);

        return $this->success(
            new EnterpriseInvitationResource($invitation->load(['team', 'inviter', 'reportsTo.user'])),
            'Invitation resent.'
        );
    }

    public function revoke(
        Request $request,
        EnterpriseInvitation $invitation,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $this->ensureInvitationBelongs($request, $invitation);

        if (!$invitation->isPending()) {
            return $this->error('Only pending invitations can be revoked.', 422);
        }

        $invitation->update(['revoked_at' => now()]);

        $audit->log($this->enterprise($request), $request->user(), 'member.invitation_revoked', $invitation);

        return $this->success(
            new EnterpriseInvitationResource($invitation->load(['team', 'inviter', 'reportsTo.user'])),
            'Invitation revoked.'
        );
    }

    private function createInvitation(
        int $enterpriseId,
        string $email,
        string $role,
        ?int $teamId,
        ?int $reportsToMembershipId,
        int $invitedBy,
    ): array {
        $rawToken = Str::random(64);

        $invitation = EnterpriseInvitation::create([
            'enterprise_id' => $enterpriseId,
            'email' => $email,
            'role' => $role,
            'team_id' => $teamId,
            'reports_to_membership_id' => $reportsToMembershipId,
            'invited_by' => $invitedBy,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(7),
        ]);

        return [$invitation, $rawToken];
    }

    private function ensureInvitationBelongs(Request $request, EnterpriseInvitation $invitation): void
    {
        abort_if($invitation->enterprise_id !== $this->enterprise($request)->id, 404);
    }

    private function inviteUrl(Request $request, string $rawToken): string
    {
        $base = rtrim(
            $request->header('origin') ?: config('app.frontend_url', 'http://localhost:5173'),
            '/'
        );

        return "{$base}/enterprise/invite/{$rawToken}";
    }

    private function sendInvitationEmail(EnterpriseInvitation $invitation, string $inviteUrl): void
    {
        try {
            $inviter = User::find($invitation->invited_by);
            $enterpriseName = $invitation->enterprise?->name ?? 'an enterprise';

            Mail::raw(
                "{$inviter?->name} invited you to join {$enterpriseName} as {$invitation->role}.\n\n"
                . "Accept your invitation: {$inviteUrl}\n\n"
                . 'This link expires in 7 days and can be used once.',
                fn ($message) => $message->to($invitation->email)->subject("You're invited to {$enterpriseName} on Rumu")
            );
        } catch (\Throwable $e) {
            Log::warning('Enterprise invitation email failed', ['invitation_id' => $invitation->id, 'error' => $e->getMessage()]);
        }
    }
}
