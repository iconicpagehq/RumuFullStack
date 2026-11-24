<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\InventoryProject;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Notification;
use App\Models\SiteVisit;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterpriseLeadService
{
    public const STAGES = Lead::ENTERPRISE_STAGES;

    private const TRANSITIONS = [
        'new' => ['contacted', 'qualified', 'site_visit_scheduled', 'lost', 'on_hold'],
        'contacted' => ['qualified', 'site_visit_scheduled', 'lost', 'on_hold'],
        'qualified' => ['site_visit_scheduled', 'negotiation', 'won', 'lost', 'on_hold'],
        'site_visit_scheduled' => ['site_visit_completed', 'negotiation', 'lost', 'on_hold'],
        'site_visit_completed' => ['negotiation', 'won', 'lost', 'on_hold'],
        'negotiation' => ['won', 'lost', 'on_hold'],
        'on_hold' => ['contacted', 'qualified', 'site_visit_scheduled', 'lost'],
        'won' => [],
        'lost' => [],
    ];

    private const LEGACY_STATUS = [
        'new' => 'new',
        'contacted' => 'contacted',
        'qualified' => 'qualified',
        'site_visit_scheduled' => 'qualified',
        'site_visit_completed' => 'qualified',
        'negotiation' => 'negotiation',
        'won' => 'won',
        'lost' => 'lost',
        'on_hold' => 'contacted',
    ];

    public function __construct(
        private EnterpriseAuditService $audit,
        private EnterpriseLimitService $limits,
        private EnterpriseHierarchyService $hierarchy,
    ) {}

    public function visibleQuery(EnterpriseMembership $membership)
    {
        return Lead::query()->visibleTo($membership);
    }

    public function canAccess(Lead $lead, EnterpriseMembership $membership): bool
    {
        return $this->hierarchy->leadVisible($lead, $membership);
    }

    public function managerTeamIds(EnterpriseMembership $membership)
    {
        return collect($this->hierarchy->managedTeamIds($membership));
    }

    public function create(
        Enterprise $enterprise,
        User $actor,
        EnterpriseMembership $membership,
        array $data,
    ): Lead {
        $this->limits->ensure($enterprise, 'monthly_leads');

        if (empty($data['allow_duplicate'])) {
            $duplicate = $this->findDuplicate($enterprise, $data);
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'duplicate' => ["A lead with this phone or email already exists ({$duplicate->name}, #{$duplicate->id})."],
                ]);
            }
        }

        $campaign = null;
        if (!empty($data['campaign_id'])) {
            $campaign = Campaign::where('enterprise_id', $enterprise->id)->find($data['campaign_id']);
            if (!$campaign) {
                throw ValidationException::withMessages(['campaign_id' => ['Select a campaign from this enterprise.']]);
            }
        }

        $project = null;
        if (!empty($data['inventory_project_id'])) {
            $project = InventoryProject::where('enterprise_id', $enterprise->id)->find($data['inventory_project_id']);
            if (!$project) {
                throw ValidationException::withMessages(['inventory_project_id' => ['Select a project from this enterprise.']]);
            }
        }

        $unit = null;
        if (!empty($data['inventory_unit_id'])) {
            $unit = InventoryUnit::query()->forEnterprise($enterprise->id)->find($data['inventory_unit_id']);
            if (!$unit) {
                throw ValidationException::withMessages(['inventory_unit_id' => ['Select a unit from this enterprise inventory.']]);
            }
        }

        $team = null;
        if (!empty($data['team_id'])) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($data['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
            if ($membership->isManager() && !$this->managerTeamIds($membership)->contains($team->id)) {
                throw ValidationException::withMessages(['team_id' => ['You can only create leads for your own team.']]);
            }
        }

        $lead = Lead::create([
            'campaign_id' => $campaign?->id,
            'enterprise_id' => $enterprise->id,
            'team_id' => $team?->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'status' => 'new',
            'enterprise_stage' => 'new',
            'priority' => $data['priority'] ?? 'medium',
            'source' => $data['source'] ?? 'manual',
            'custom_fields' => $data['custom_fields'] ?? null,
            'inventory_project_id' => $project?->id,
            'inventory_unit_id' => $unit?->id,
            'budget_min' => $data['budget_min'] ?? null,
            'budget_max' => $data['budget_max'] ?? null,
            'preferred_location' => $data['preferred_location'] ?? null,
            'preferred_configuration' => $data['preferred_configuration'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
        ]);

        $this->activity($lead, $actor, 'created', 'Lead created', $data['note'] ?? null, [
            'source' => $lead->source,
        ]);

        if (!empty($data['assigned_to'])) {
            $this->assign($lead, $enterprise, $actor, $membership, (int) $data['assigned_to'], $team);
        } elseif ($team) {
            $autoAssign = $team->lead_distribution_mode === 'round_robin'
                || (bool) $enterprise->setting('auto_assign_round_robin', false);
            if ($autoAssign) {
                $this->roundRobinAssign($lead, $team, $actor, $membership);
            }
        }

        $this->audit->log($enterprise, $actor, 'lead.created', $lead, 'Lead created', [
            'source' => $lead->source,
        ]);

        return $lead->fresh();
    }

    public function updateDetails(
        Lead $lead,
        Enterprise $enterprise,
        User $actor,
        EnterpriseMembership $membership,
        array $data,
    ): Lead {
        if ($membership->isEmployee() && array_key_exists('team_id', $data)) {
            throw ValidationException::withMessages(['team_id' => ['You cannot move this lead to another team.']]);
        }

        if (!empty($data['team_id'])) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($data['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
            if ($membership->isManager() && !$this->managerTeamIds($membership)->contains($team->id)) {
                throw ValidationException::withMessages(['team_id' => ['You can only move leads within your own team.']]);
            }
            $data['team_id'] = $team->id;
        }

        if (!empty($data['campaign_id'])) {
            $campaign = Campaign::where('enterprise_id', $enterprise->id)->find($data['campaign_id']);
            if (!$campaign) {
                throw ValidationException::withMessages(['campaign_id' => ['Select a campaign from this enterprise.']]);
            }
        }

        if (!empty($data['inventory_project_id'])) {
            $project = InventoryProject::where('enterprise_id', $enterprise->id)->find($data['inventory_project_id']);
            if (!$project) {
                throw ValidationException::withMessages(['inventory_project_id' => ['Select a project from this enterprise.']]);
            }
        }

        if (!empty($data['inventory_unit_id'])) {
            $unit = InventoryUnit::query()->forEnterprise($enterprise->id)->find($data['inventory_unit_id']);
            if (!$unit) {
                throw ValidationException::withMessages(['inventory_unit_id' => ['Select a unit from this enterprise inventory.']]);
            }
        }

        $lead->update(collect($data)->only([
            'name', 'email', 'phone', 'company', 'priority', 'source',
            'custom_fields', 'budget_min', 'budget_max', 'preferred_location',
            'preferred_configuration', 'next_follow_up_at', 'campaign_id',
            'inventory_project_id', 'inventory_unit_id', 'team_id',
        ])->all());

        $this->activity($lead, $actor, 'updated', 'Lead details updated');

        return $lead->fresh();
    }

    public function assign(
        Lead $lead,
        Enterprise $enterprise,
        User $actor,
        EnterpriseMembership $actorMembership,
        int $assigneeId,
        ?Team $team = null,
        string $method = 'manual',
    ): Lead {
        return DB::transaction(function () use ($lead, $enterprise, $actor, $actorMembership, $assigneeId, $team, $method) {
            $lead = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($lead->enterprise_id !== $enterprise->id) {
                throw ValidationException::withMessages(['lead' => ['This lead belongs to another enterprise.']]);
            }

            $assigneeMembership = EnterpriseMembership::query()
                ->where('enterprise_id', $enterprise->id)
                ->where('user_id', $assigneeId)
                ->where('status', 'active')
                ->whereIn('role', ['employee', 'manager'])
                ->first();

            if (!$assigneeMembership) {
                throw ValidationException::withMessages(['assigned_to' => ['Select an active employee or manager in this enterprise.']]);
            }

            $targetTeam = $team;
            if ($targetTeam && $targetTeam->enterprise_id !== $enterprise->id) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }

            if ($actorMembership->isManager()) {
                $allowedTeams = $this->managerTeamIds($actorMembership);
                if ($targetTeam && !$allowedTeams->contains((int) $targetTeam->id)) {
                    throw ValidationException::withMessages(['team_id' => ['You can only assign leads within your own team.']]);
                }

                if (!$this->hierarchy->canAssignTo($actorMembership, $assigneeMembership)) {
                    throw ValidationException::withMessages([
                        'assigned_to' => ['You can only assign leads to your reporting line or your team.'],
                    ]);
                }
            } elseif ($actorMembership->isEmployee()) {
                $selfAssignment = (int) $assigneeMembership->id === (int) $actorMembership->id;

                if (!$selfAssignment) {
                    if (!$this->hierarchy->hasReports($actorMembership)) {
                        throw ValidationException::withMessages([
                            'assigned_to' => ['You cannot assign leads.'],
                        ]);
                    }

                    if (!$this->hierarchy->canAssignTo($actorMembership, $assigneeMembership)) {
                        throw ValidationException::withMessages([
                            'assigned_to' => ['You can only assign leads to members of your reporting line.'],
                        ]);
                    }

                    if ($targetTeam && (int) $targetTeam->id !== (int) $lead->team_id) {
                        throw ValidationException::withMessages([
                            'team_id' => ['You cannot move this lead to another team.'],
                        ]);
                    }
                }
            }

            if (!$targetTeam) {
                $primary = TeamMembership::where('enterprise_id', $enterprise->id)
                    ->where('user_id', $assigneeId)
                    ->orderByDesc('is_primary')
                    ->first();
                $targetTeam = $primary ? Team::where('enterprise_id', $enterprise->id)->find($primary->team_id) : null;
            }

            if ($targetTeam && $assigneeMembership->isEmployee()
                && !TeamMembership::where('team_id', $targetTeam->id)->where('user_id', $assigneeId)->exists()) {
                throw ValidationException::withMessages([
                    'assigned_to' => ['The selected assignee is not a member of this team.'],
                ]);
            }

            $previousAssignee = $lead->assigned_to;

            if ($previousAssignee === $assigneeId && $lead->team_id === $targetTeam?->id) {
                return $lead;
            }

            $lead->assigned_to = $assigneeId;
            $lead->assigned_at = now();
            $lead->team_id = $targetTeam?->id;
            $lead->assigned_manager_id = $targetTeam?->manager_id;
            $lead->save();

            if ($targetTeam) {
                TeamMembership::where('team_id', $targetTeam->id)
                    ->where('user_id', $assigneeId)
                    ->update(['last_assigned_at' => now()]);
            }

            $type = $previousAssignee ? 'reassigned' : 'assigned';
            $this->activity($lead, $actor, $type, $previousAssignee ? 'Lead reassigned' : 'Lead assigned', null, [
                'from_user_id' => $previousAssignee,
                'to_user_id' => $assigneeId,
                'method' => $method,
                'team_id' => $targetTeam?->id,
            ]);

            if ($assigneeId !== $actor->id) {
                $this->notify(
                    $assigneeId,
                    'lead_assigned',
                    $previousAssignee ? 'Lead reassigned to you' : 'New lead assigned',
                    "{$lead->name} is now assigned to you.",
                    $lead,
                );
            }

            if ($targetTeam?->manager_id && !in_array($targetTeam->manager_id, [$actor->id, $assigneeId], true)) {
                $this->notify(
                    $targetTeam->manager_id,
                    'lead_assignment',
                    'Team lead assignment',
                    "{$lead->name} was assigned to {$assigneeMembership->user->name}.",
                    $lead,
                );
            }

            $this->audit->log($enterprise, $actor, $type === 'assigned' ? 'lead.assigned' : 'lead.reassigned', $lead, null, [
                'from_user_id' => $previousAssignee,
                'to_user_id' => $assigneeId,
                'team_id' => $targetTeam?->id,
            ]);

            return $lead->fresh();
        });
    }

    public function roundRobinAssign(
        Lead $lead,
        Team $team,
        User $actor,
        EnterpriseMembership $actorMembership,
    ): ?Lead {
        return DB::transaction(function () use ($lead, $team, $actor, $actorMembership) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();

            $candidate = TeamMembership::query()
                ->where('team_id', $team->id)
                ->where('enterprise_id', $team->enterprise_id)
                ->whereHas('user.enterpriseMembership', function ($query) use ($team) {
                    $query->where('enterprise_id', $team->enterprise_id)
                        ->where('status', 'active')
                        ->whereIn('role', ['employee', 'manager']);
                })
                ->orderByRaw('CASE WHEN last_assigned_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('last_assigned_at')
                ->orderByRaw('(SELECT COUNT(*) FROM leads WHERE leads.assigned_to = team_memberships.user_id AND leads.team_id = team_memberships.team_id AND leads.deleted_at IS NULL)')
                ->orderBy('id')
                ->first();

            if (!$candidate) {
                return null;
            }

            return $this->assign(
                $lead,
                $team->enterprise,
                $actor,
                $actorMembership,
                $candidate->user_id,
                $team,
                'round_robin',
            );
        });
    }

    public function distribute(
        Enterprise $enterprise,
        User $actor,
        EnterpriseMembership $actorMembership,
        Team $team,
        array $leadIds,
        string $mode,
        ?int $assigneeId = null,
    ): array {
        if ($team->enterprise_id !== $enterprise->id) {
            throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
        }

        if ($actorMembership->isManager() && !$this->managerTeamIds($actorMembership)->contains($team->id)) {
            throw ValidationException::withMessages(['team_id' => ['You can only distribute leads within your own team.']]);
        }

        $leadIds = array_values(array_unique(array_map('intval', $leadIds)));

        $leads = $this->visibleQuery($actorMembership)
            ->where('enterprise_id', $enterprise->id)
            ->whereIn('id', $leadIds)
            ->get()
            ->keyBy('id');

        if ($leads->count() !== count($leadIds)) {
            throw ValidationException::withMessages([
                'lead_ids' => ['Some selected leads are not available in your workspace.'],
            ]);
        }

        if ($mode === 'manual' && !$assigneeId) {
            throw ValidationException::withMessages(['assigned_to' => ['Select a team member to receive these leads.']]);
        }

        return DB::transaction(function () use ($enterprise, $actor, $actorMembership, $team, $leadIds, $leads, $mode, $assigneeId) {
            $assignments = [];

            foreach ($leadIds as $leadId) {
                $lead = $leads->get($leadId);
                $assigned = $mode === 'manual'
                    ? $this->assign($lead, $enterprise, $actor, $actorMembership, (int) $assigneeId, $team)
                    : $this->roundRobinAssign($lead, $team, $actor, $actorMembership);

                if (!$assigned) {
                    throw ValidationException::withMessages([
                        'team_id' => ['No eligible team members are available for round-robin distribution.'],
                    ]);
                }

                $assignments[] = [
                    'lead_id' => $assigned->id,
                    'lead_name' => $assigned->name,
                    'assigned_to' => $assigned->assigned_to,
                    'assignee_name' => $assigned->assignee?->name,
                ];
            }

            $this->audit->log($enterprise, $actor, 'leads.distributed', $team, 'Leads distributed', [
                'team_id' => $team->id,
                'mode' => $mode,
                'lead_ids' => $leadIds,
                'assigned_to' => $mode === 'manual' ? $assigneeId : null,
                'count' => count($assignments),
            ]);

            return [
                'assigned_count' => count($assignments),
                'assignments' => $assignments,
            ];
        });
    }

    public function updateStage(
        Lead $lead,
        Enterprise $enterprise,
        User $actor,
        EnterpriseMembership $membership,
        string $stage,
        array $data,
    ): Lead {
        if (!in_array($stage, self::STAGES, true)) {
            throw ValidationException::withMessages(['stage' => ['Invalid pipeline stage.']]);
        }

        $current = $lead->enterprise_stage ?: 'new';
        if ($stage === $current) {
            throw ValidationException::withMessages(['stage' => ['The lead is already in this stage.']]);
        }

        if (!in_array($stage, self::TRANSITIONS[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'stage' => ["Leads cannot move from {$current} to {$stage}."],
            ]);
        }

        $note = trim((string) ($data['note'] ?? ''));

        if (in_array($stage, ['contacted', 'qualified'], true) && $note === '') {
            throw ValidationException::withMessages(['note' => ['Add a short note describing the interaction.']]);
        }

        if ($stage === 'qualified') {
            $hasDetail = collect([
                $data['budget_min'] ?? $lead->budget_min,
                $data['budget_max'] ?? $lead->budget_max,
                $data['preferred_location'] ?? $lead->preferred_location,
                $data['preferred_configuration'] ?? $lead->preferred_configuration,
                $data['next_follow_up_at'] ?? $lead->next_follow_up_at,
            ])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();

            if (!$hasDetail) {
                throw ValidationException::withMessages([
                    'qualification' => ['Add budget, location, configuration or a follow-up before qualifying this lead.'],
                ]);
            }
        }

        if ($stage === 'site_visit_scheduled') {
            $hasVisit = SiteVisit::where('lead_id', $lead->id)
                ->whereIn('status', ['scheduled', 'completed'])
                ->exists();
            if (!$hasVisit) {
                throw ValidationException::withMessages(['stage' => ['Schedule a site visit before moving this lead to Site Visit Scheduled.']]);
            }
        }

        if ($stage === 'site_visit_completed') {
            $hasCompleted = SiteVisit::where('lead_id', $lead->id)->where('status', 'completed')->exists();
            if (!$hasCompleted) {
                throw ValidationException::withMessages(['stage' => ['Mark a site visit as completed first.']]);
            }
        }

        if ($stage === 'won' && empty($data['deal_value'])) {
            throw ValidationException::withMessages(['deal_value' => ['Deal value is required when marking a lead won.']]);
        }

        if ($stage === 'lost' && trim((string) ($data['lost_reason'] ?? '')) === '') {
            throw ValidationException::withMessages(['lost_reason' => ['A lost reason is required.']]);
        }

        if ($stage === 'on_hold' && empty($data['next_follow_up_at'])) {
            throw ValidationException::withMessages(['next_follow_up_at' => ['Set the next follow-up before putting a lead on hold.']]);
        }

        return DB::transaction(function () use ($lead, $enterprise, $actor, $membership, $stage, $current, $data, $note) {
            $updates = [
                'enterprise_stage' => $stage,
                'status' => self::LEGACY_STATUS[$stage],
            ];

            if ($stage === 'contacted') {
                $updates['last_contacted_at'] = now();
            }

            if (in_array($stage, ['contacted', 'qualified', 'site_visit_scheduled', 'site_visit_completed', 'negotiation', 'on_hold'], true)) {
                $updates['next_follow_up_at'] = $data['next_follow_up_at'] ?? $lead->next_follow_up_at;
            }

            if ($stage === 'qualified') {
                $updates['budget_min'] = $data['budget_min'] ?? $lead->budget_min;
                $updates['budget_max'] = $data['budget_max'] ?? $lead->budget_max;
                $updates['preferred_location'] = $data['preferred_location'] ?? $lead->preferred_location;
                $updates['preferred_configuration'] = $data['preferred_configuration'] ?? $lead->preferred_configuration;
            }

            if ($stage === 'won') {
                $updates['deal_value'] = $data['deal_value'];
                $updates['won_at'] = now();
                $updates['next_follow_up_at'] = null;
            }

            if ($stage === 'lost') {
                $updates['lost_reason'] = $data['lost_reason'];
                $updates['next_follow_up_at'] = null;
            }

            $lead->update($updates);

            $this->activity($lead, $actor, 'status_changed', "Stage changed to " . $this->stageLabel($stage), $note, [
                'from' => $current,
                'to' => $stage,
                'deal_value' => $stage === 'won' ? $data['deal_value'] : null,
                'lost_reason' => $stage === 'lost' ? $data['lost_reason'] : null,
            ]);

            if ($stage === 'won') {
                $this->notifyDealClosed($lead, $enterprise, $actor, 'won');
            }

            if ($stage === 'lost') {
                $this->notifyDealClosed($lead, $enterprise, $actor, 'lost');
            }

            if ($stage === 'contacted' && $lead->assigned_to && $lead->assigned_to !== $actor->id) {
                $this->notify(
                    $lead->assigned_to,
                    'lead_activity',
                    'Lead contacted',
                    "{$lead->name} was marked contacted by {$actor->name}.",
                    $lead,
                );
            }

            $this->audit->log($enterprise, $actor, 'lead.stage_changed', $lead, null, [
                'from' => $current,
                'to' => $stage,
            ]);

            return $lead->fresh();
        });
    }

    public function addNote(Lead $lead, User $actor, string $note): LeadActivity
    {
        return $this->activity($lead, $actor, 'note', 'Note added', $note);
    }

    public function delete(Lead $lead, Enterprise $enterprise, User $actor): void
    {
        DB::transaction(function () use ($lead, $enterprise, $actor) {
            $this->audit->log($enterprise, $actor, 'lead.deleted', $lead, 'Lead deleted', [
                'name' => $lead->name,
                'stage' => $lead->enterprise_stage,
            ]);

            $lead->delete();
        });
    }

    public function activity(
        Lead $lead,
        ?User $actor,
        string $type,
        string $title,
        ?string $description = null,
        array $meta = [],
    ): LeadActivity {
        return LeadActivity::create([
            'enterprise_id' => $lead->enterprise_id,
            'lead_id' => $lead->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }

    public function stageLabel(string $stage): string
    {
        return ucwords(str_replace('_', ' ', $stage));
    }

    private function findDuplicate(Enterprise $enterprise, array $data): ?Lead
    {
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($phone === '' && $email === '') {
            return null;
        }

        return Lead::where('enterprise_id', $enterprise->id)
            ->where(function ($query) use ($phone, $email) {
                if ($phone !== '') {
                    $query->where('phone', $phone);
                }
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }
            })
            ->first();
    }

    private function notifyDealClosed(Lead $lead, Enterprise $enterprise, User $actor, string $result): void
    {
        $recipients = collect([$lead->assigned_to, $lead->assigned_manager_id])
            ->filter()
            ->merge(
                EnterpriseMembership::where('enterprise_id', $enterprise->id)
                    ->where('role', 'admin')
                    ->where('status', 'active')
                    ->pluck('user_id')
            )
            ->unique()
            ->reject(fn ($id) => $id === $actor->id);

        foreach ($recipients as $userId) {
            $this->notify(
                (int) $userId,
                'deal_closure',
                $result === 'won' ? 'Deal won' : 'Deal lost',
                $result === 'won'
                    ? "{$lead->name} was marked won by {$actor->name}."
                    : "{$lead->name} was marked lost by {$actor->name}.",
                $lead,
            );
        }
    }

    private function notify(int $userId, string $type, string $title, string $message, Lead $lead): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $lead->id,
            'related_type' => 'lead',
        ]);
    }
}
