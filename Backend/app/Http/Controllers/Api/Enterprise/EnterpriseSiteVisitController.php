<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseSiteVisitResource;
use App\Models\EnterpriseMembership;
use App\Models\InventoryProject;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\SiteVisit;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Services\EnterpriseAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnterpriseSiteVisitController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);

        $query = SiteVisit::where('enterprise_id', $membership->enterprise_id)
            ->with(['lead', 'project', 'unit', 'team', 'assignee', 'creator']);

        $this->applyScope($query, $request);

        if ($status = $request->input('status')) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('status', array_intersect($statuses, SiteVisit::STATUSES));
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('team_id', $teamId);
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('inventory_project_id', $projectId);
        }

        if ($leadId = $request->input('lead_id')) {
            $query->where('lead_id', $leadId);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($from = $request->input('from')) {
            $query->where('scheduled_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('scheduled_at', '<=', $to);
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        if ($request->input('range') === 'today') {
            $query->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()]);
        }

        $query->orderBy('scheduled_at');

        $paginator = $query->paginate((int) $request->input('per_page', 20));

        $statsQuery = SiteVisit::where('enterprise_id', $membership->enterprise_id);
        $this->applyScope($statsQuery, $request);

        return response()->json([
            'success' => true,
            'data' => EnterpriseSiteVisitResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'stats' => [
                    'scheduled' => (clone $statsQuery)->where('status', 'scheduled')->count(),
                    'upcoming' => (clone $statsQuery)->upcoming()->count(),
                    'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
                    'cancelled' => (clone $statsQuery)->whereIn('status', ['cancelled', 'no_show'])->count(),
                ],
            ],
            'message' => null,
        ]);
    }

    public function store(Request $request, EnterpriseAuditService $audit, \App\Services\EnterpriseLeadService $leadService)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'lead_id' => 'required|integer',
            'inventory_project_id' => 'required|integer',
            'inventory_unit_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $lead = $this->findAccessibleLead($request, (int) $validated['lead_id'], $leadService);
        $project = $this->findProject($request, (int) $validated['inventory_project_id']);
        $unit = $this->findUnit($request, $validated['inventory_unit_id'] ?? null, $project->id);

        $assigneeId = (int) ($validated['assigned_to'] ?? $membership->user_id);
        $this->ensureAssignee($request, $assigneeId, $lead);

        $visit = SiteVisit::create([
            'enterprise_id' => $enterprise->id,
            'team_id' => $lead->team_id,
            'lead_id' => $lead->id,
            'inventory_project_id' => $project->id,
            'inventory_unit_id' => $unit?->id,
            'assigned_to' => $assigneeId,
            'created_by' => $request->user()->id,
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled',
        ]);

        $leadService->activity($lead, $request->user(), 'site_visit_scheduled', 'Site visit scheduled', $validated['notes'] ?? null, [
            'site_visit_id' => $visit->id,
            'scheduled_at' => $visit->scheduled_at?->toIso8601String(),
            'project_id' => $project->id,
        ]);

        if ($lead->next_follow_up_at === null || $lead->next_follow_up_at->lt($visit->scheduled_at)) {
            $lead->update(['next_follow_up_at' => $visit->scheduled_at]);
        }

        if ($assigneeId !== (int) $request->user()->id) {
            $this->notify($assigneeId, 'site_visit_scheduled', 'Site visit scheduled', "{$lead->name} · {$project->name}", $visit);
        }

        $audit->log($enterprise, $request->user(), 'site_visit.created', $visit, 'Site visit scheduled', [
            'lead_id' => $lead->id,
            'project_id' => $project->id,
        ]);

        return $this->success(
            new EnterpriseSiteVisitResource($visit->load(['lead', 'project', 'unit', 'team', 'assignee', 'creator'])),
            'Site visit scheduled.',
            201
        );
    }

    public function show(Request $request, SiteVisit $siteVisit)
    {
        $this->ensureVisitVisible($request, $siteVisit);

        $siteVisit->load(['lead', 'project', 'unit', 'team', 'assignee', 'creator']);

        return $this->success(new EnterpriseSiteVisitResource($siteVisit));
    }

    public function update(
        Request $request,
        SiteVisit $siteVisit,
        EnterpriseAuditService $audit,
    ) {
        $this->ensureVisitVisible($request, $siteVisit);

        if ($siteVisit->status !== 'scheduled') {
            return $this->error('Only scheduled visits can be rescheduled.', 422);
        }

        $validated = $request->validate([
            'inventory_project_id' => 'sometimes|required|integer',
            'inventory_unit_id' => 'nullable|integer',
            'assigned_to' => 'sometimes|integer',
            'scheduled_at' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $project = isset($validated['inventory_project_id'])
            ? $this->findProject($request, (int) $validated['inventory_project_id'])
            : $siteVisit->project;

        $unit = $this->findUnit(
            $request,
            $validated['inventory_unit_id'] ?? $siteVisit->inventory_unit_id,
            $project->id
        );

        if (isset($validated['assigned_to'])) {
            $this->ensureAssignee($request, (int) $validated['assigned_to'], $siteVisit->lead);
        }

        $siteVisit->update([
            'inventory_project_id' => $project->id,
            'inventory_unit_id' => $unit?->id,
            'assigned_to' => $validated['assigned_to'] ?? $siteVisit->assigned_to,
            'scheduled_at' => $validated['scheduled_at'] ?? $siteVisit->scheduled_at,
        ]);

        $audit->log($this->enterprise($request), $request->user(), 'site_visit.updated', $siteVisit);

        return $this->success(
            new EnterpriseSiteVisitResource($siteVisit->fresh()->load(['lead', 'project', 'unit', 'team', 'assignee', 'creator'])),
            'Site visit updated.'
        );
    }

    public function updateStatus(
        Request $request,
        SiteVisit $siteVisit,
        EnterpriseAuditService $audit,
        \App\Services\EnterpriseLeadService $leadService,
    ) {
        $this->ensureVisitVisible($request, $siteVisit);

        $validated = $request->validate([
            'status' => 'required|in:completed,cancelled,no_show',
            'customer_attended' => 'nullable|boolean',
            'outcome' => 'nullable|string|max:2000',
            'feedback' => 'nullable|string|max:5000',
            'next_action' => 'nullable|string|max:1000',
            'cancellation_reason' => 'nullable|string|max:1000',
            'create_follow_up' => 'sometimes|boolean',
            'follow_up_title' => 'nullable|string|max:255',
            'follow_up_due_at' => 'nullable|date',
        ]);

        if ($siteVisit->status !== 'scheduled') {
            return $this->error('Only scheduled visits can be closed.', 422);
        }

        if ($validated['status'] === 'completed' && empty($validated['outcome'])) {
            throw ValidationException::withMessages(['outcome' => ['Record the visit outcome.']]);
        }

        if ($validated['status'] === 'cancelled' && empty($validated['cancellation_reason'])) {
            throw ValidationException::withMessages(['cancellation_reason' => ['Provide a cancellation reason.']]);
        }

        $siteVisit->update([
            'status' => $validated['status'],
            'customer_attended' => $validated['customer_attended'] ?? $siteVisit->customer_attended,
            'outcome' => $validated['outcome'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
            'next_action' => $validated['next_action'] ?? null,
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        $leadService->activity(
            $siteVisit->lead,
            $request->user(),
            'site_visit_' . $validated['status'],
            'Site visit ' . str_replace('_', ' ', $validated['status']),
            $validated['outcome'] ?? $validated['cancellation_reason'] ?? null,
            ['site_visit_id' => $siteVisit->id]
        );

        if ($validated['status'] === 'completed' && $request->boolean('create_follow_up')) {
            Task::create([
                'enterprise_id' => $siteVisit->enterprise_id,
                'team_id' => $siteVisit->team_id,
                'lead_id' => $siteVisit->lead_id,
                'assigned_to' => $siteVisit->assigned_to,
                'created_by' => $request->user()->id,
                'title' => $validated['follow_up_title'] ?? "Follow up after site visit · {$siteVisit->lead?->name}",
                'description' => $validated['next_action'] ?? null,
                'priority' => 'medium',
                'due_at' => $validated['follow_up_due_at'] ?? now()->addDay(),
                'status' => 'pending',
            ]);
        }

        $audit->log($this->enterprise($request), $request->user(), 'site_visit.status_changed', $siteVisit, null, [
            'status' => $validated['status'],
        ]);

        return $this->success(
            new EnterpriseSiteVisitResource($siteVisit->fresh()->load(['lead', 'project', 'unit', 'team', 'assignee', 'creator'])),
            'Site visit updated.'
        );
    }

    public function destroy(Request $request, SiteVisit $siteVisit, EnterpriseAuditService $audit)
    {
        $this->ensureVisitVisible($request, $siteVisit);

        if ($siteVisit->status === 'completed') {
            return $this->error('Completed visits are kept for history.', 422);
        }

        $siteVisit->delete();
        $audit->log($this->enterprise($request), $request->user(), 'site_visit.deleted', $siteVisit);

        return $this->success(null, 'Site visit deleted.');
    }

    private function applyScope($query, Request $request): void
    {
        $this->hierarchyService()->applyVisitScope($query, $this->membership($request));
    }

    private function ensureVisitVisible(Request $request, SiteVisit $siteVisit): void
    {
        $membership = $this->membership($request);

        abort_if((int) $siteVisit->enterprise_id !== (int) $membership->enterprise_id, 404);

        abort_if(
            !$this->hierarchyService()->visitVisible($siteVisit, $membership),
            403,
            'This site visit is outside your scope.'
        );
    }

    private function findAccessibleLead(Request $request, int $leadId, \App\Services\EnterpriseLeadService $leadService): Lead
    {
        $lead = Lead::where('enterprise_id', $this->enterprise($request)->id)->find($leadId);

        if (!$lead) {
            throw ValidationException::withMessages(['lead_id' => ['Select a lead from this enterprise.']]);
        }

        if (!$leadService->canAccess($lead, $this->membership($request))) {
            throw ValidationException::withMessages(['lead_id' => ['This lead is outside your scope.']]);
        }

        return $lead;
    }

    private function findProject(Request $request, int $projectId): InventoryProject
    {
        $project = InventoryProject::where('enterprise_id', $this->enterprise($request)->id)->find($projectId);

        if (!$project) {
            throw ValidationException::withMessages(['inventory_project_id' => ['Select a project from this enterprise.']]);
        }

        return $project;
    }

    private function findUnit(Request $request, mixed $unitId, int $projectId): ?InventoryUnit
    {
        if (!$unitId) {
            return null;
        }

        $unit = InventoryUnit::query()->forEnterprise($this->enterprise($request)->id)->find($unitId);

        if (!$unit || (int) $unit->floor?->tower?->inventory_project_id !== $projectId) {
            throw ValidationException::withMessages(['inventory_unit_id' => ['Select a unit from the selected project.']]);
        }

        return $unit;
    }

    private function ensureAssignee(Request $request, int $assigneeId, Lead $lead): void
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $assignee = EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('user_id', $assigneeId)
            ->where('status', 'active')
            ->first();

        if (!$assignee) {
            throw ValidationException::withMessages(['assigned_to' => ['Select an active enterprise member.']]);
        }

        if (!$membership->isAdmin() && !$this->hierarchyService()->canAssignTo($membership, $assignee)) {
            throw ValidationException::withMessages([
                'assigned_to' => ['You can only schedule visits within your reporting line or your team.'],
            ]);
        }

        if (!$lead->team_id) {
            $lead->update([
                'team_id' => TeamMembership::where('user_id', $assigneeId)->value('team_id'),
                'assigned_to' => $lead->assigned_to ?: $assigneeId,
                'assigned_at' => $lead->assigned_at ?: now(),
            ]);
        }
    }

    private function notify(int $userId, string $type, string $title, string $message, SiteVisit $visit): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $visit->id,
            'related_type' => 'site_visit',
        ]);
    }
}
