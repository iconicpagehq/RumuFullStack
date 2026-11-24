<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseTaskResource;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Services\EnterpriseAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterpriseTaskController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $membership = $this->membership($request);

        $query = Task::where('enterprise_id', $membership->enterprise_id)
            ->with(['lead', 'team', 'assignee', 'creator']);

        $this->applyScope($query, $request);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('status', array_intersect($statuses, Task::STATUSES));
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('team_id', $teamId);
        }

        if ($leadId = $request->input('lead_id')) {
            $query->where('lead_id', $leadId);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        if ($request->input('due') === 'today') {
            $query->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
                ->whereNotIn('status', ['completed', 'cancelled']);
        }

        if ($request->input('due') === 'upcoming') {
            $query->where('due_at', '>=', now())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->orderBy('due_at');
        } else {
            $query->orderByRaw('due_at IS NULL')->orderBy('due_at')->latest('id');
        }

        $paginator = $query->paginate((int) $request->input('per_page', 20));

        $statsQuery = Task::where('enterprise_id', $membership->enterprise_id);
        $this->applyScope($statsQuery, $request);

        return response()->json([
            'success' => true,
            'data' => EnterpriseTaskResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'stats' => [
                    'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                    'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
                    'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
                    'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
                    'overdue' => (clone $statsQuery)->overdue()->count(),
                    'due_today' => (clone $statsQuery)->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->whereNotIn('status', ['completed', 'cancelled'])->count(),
                    'urgent' => (clone $statsQuery)->where('priority', 'urgent')->count(),
                    'high' => (clone $statsQuery)->where('priority', 'high')->count(),
                    'medium' => (clone $statsQuery)->where('priority', 'medium')->count(),
                    'low' => (clone $statsQuery)->where('priority', 'low')->count(),
                ],
            ],
            'message' => null,
        ]);
    }

    public function store(Request $request, EnterpriseAuditService $audit)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
            'lead_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'team_id' => 'nullable|integer',
        ]);

        $lead = null;
        if (!empty($validated['lead_id'])) {
            $lead = Lead::where('enterprise_id', $enterprise->id)->find($validated['lead_id']);
            if (!$lead) {
                throw ValidationException::withMessages(['lead_id' => ['Select a lead from this enterprise.']]);
            }
            if (!$this->canAccessLead($lead, $membership)) {
                throw ValidationException::withMessages(['lead_id' => ['This lead is outside your scope.']]);
            }
        }

        $assigneeId = $validated['assigned_to'] ?? $request->user()->id;
        $hierarchy = $this->hierarchyService();

        $assignee = EnterpriseMembership::where('enterprise_id', $enterprise->id)
            ->where('user_id', $assigneeId)
            ->where('status', 'active')
            ->whereIn('role', ['admin', 'manager', 'employee'])
            ->first();

        if (!$assignee) {
            throw ValidationException::withMessages(['assigned_to' => ['Select an active enterprise member.']]);
        }

        if ($membership->isEmployee() && !$hierarchy->canAssignTo($membership, $assignee)) {
            throw ValidationException::withMessages([
                'assigned_to' => ['You can only create tasks within your own reporting line.'],
            ]);
        }

        if ($membership->isManager() && !$hierarchy->canAssignTo($membership, $assignee)) {
            throw ValidationException::withMessages([
                'assigned_to' => ['You can only assign tasks to your reporting line or your team.'],
            ]);
        }

        $teamId = $validated['team_id'] ?? $lead?->team_id
            ?? TeamMembership::where('user_id', $assigneeId)->value('team_id');

        if ($teamId) {
            $team = Team::where('enterprise_id', $enterprise->id)->find($teamId);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
            if ($membership->isManager() && !in_array((int) $team->id, $this->teamScopeIds($request), true)) {
                throw ValidationException::withMessages(['team_id' => ['You can only create tasks within your team.']]);
            }
            if ($membership->isEmployee() && (int) $team->id !== (int) $assignee->primaryTeamMembership?->team_id) {
                throw ValidationException::withMessages(['team_id' => ['You can only create tasks within your own team.']]);
            }
        }

        $task = Task::create([
            'enterprise_id' => $enterprise->id,
            'team_id' => $teamId ?: null,
            'lead_id' => $lead?->id,
            'assigned_to' => $assigneeId,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'due_at' => $validated['due_at'] ?? null,
            'status' => 'pending',
        ]);

        if ((int) $assigneeId !== (int) $request->user()->id) {
            $this->notify($assigneeId, 'task_assigned', 'New task assigned', $task->title, $task);
        }

        $audit->log($enterprise, $request->user(), 'task.created', $task, 'Task created', [
            'assigned_to' => $assigneeId,
        ]);

        return $this->success(
            new EnterpriseTaskResource($task->load(['lead', 'team', 'assignee', 'creator'])),
            'Task created.',
            201
        );
    }

    public function show(Request $request, Task $task)
    {
        $this->ensureTaskVisible($request, $task);

        $task->load(['lead', 'team', 'assignee', 'creator']);

        return $this->success(new EnterpriseTaskResource($task));
    }

    public function update(Request $request, Task $task, EnterpriseAuditService $audit)
    {
        $this->ensureTaskVisible($request, $task);
        $membership = $this->membership($request);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
            'assigned_to' => 'sometimes|integer',
            'team_id' => 'nullable|integer',
        ]);

        if (array_key_exists('assigned_to', $validated)) {
            if ($membership->isEmployee()) {
                throw ValidationException::withMessages(['assigned_to' => ['You cannot reassign tasks.']]);
            }

            $assignee = EnterpriseMembership::where('enterprise_id', $membership->enterprise_id)
                ->where('user_id', $validated['assigned_to'])
                ->where('status', 'active')
                ->first();

            if (!$assignee) {
                throw ValidationException::withMessages(['assigned_to' => ['Select an active enterprise member.']]);
            }

            if ($membership->isManager() && !$this->hierarchyService()->canAssignTo($membership, $assignee)) {
                throw ValidationException::withMessages(['assigned_to' => ['You can only assign tasks to your reporting line or your team.']]);
            }
        }

        if (array_key_exists('team_id', $validated) && $membership->isEmployee()) {
            throw ValidationException::withMessages(['team_id' => ['You cannot change the team of a task.']]);
        }

        if (!empty($validated['team_id'])) {
            $team = Team::where('enterprise_id', $membership->enterprise_id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
            if ($membership->isManager() && !in_array((int) $team->id, $this->teamScopeIds($request), true)) {
                throw ValidationException::withMessages(['team_id' => ['This team is outside your scope.']]);
            }
        }

        $task->update(collect($validated)->only([
            'title', 'description', 'priority', 'due_at', 'assigned_to', 'team_id',
        ])->all());

        $audit->log($membership->enterprise, $request->user(), 'task.updated', $task);

        return $this->success(
            new EnterpriseTaskResource($task->fresh()->load(['lead', 'team', 'assignee', 'creator'])),
            'Task updated.'
        );
    }

    public function updateStatus(Request $request, Task $task, EnterpriseAuditService $audit)
    {
        $this->ensureTaskVisible($request, $task);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Task::STATUSES),
            'completion_notes' => 'nullable|string|max:5000',
        ]);

        $previous = $task->status;
        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'completed') {
            $updates['completed_at'] = $task->completed_at ?? now();
            $updates['completion_notes'] = $validated['completion_notes'] ?? $task->completion_notes;
        } elseif ($previous === 'completed') {
            $updates['completed_at'] = null;
        }

        $task->update($updates);

        if ($validated['status'] === 'completed' && $task->created_by && (int) $task->created_by !== (int) $request->user()->id) {
            $this->notify(
                $task->created_by,
                'task_completed',
                'Task completed',
                $request->user()->name . " completed: {$task->title}",
                $task
            );
        }

        $audit->log($this->enterprise($request), $request->user(), 'task.status_changed', $task, null, [
            'from' => $previous,
            'to' => $validated['status'],
        ]);

        return $this->success(
            new EnterpriseTaskResource($task->fresh()->load(['lead', 'team', 'assignee', 'creator'])),
            'Task status updated.'
        );
    }

    public function destroy(Request $request, Task $task, EnterpriseAuditService $audit)
    {
        $membership = $this->membership($request);
        $this->ensureTaskVisible($request, $task);

        if ($membership->isEmployee() && (int) $task->created_by !== (int) $membership->user_id) {
            abort(403, 'You can only delete tasks you created.');
        }

        $task->delete();
        $audit->log($membership->enterprise, $request->user(), 'task.deleted', $task);

        return $this->success(null, 'Task deleted.');
    }

    private function applyScope($query, Request $request): void
    {
        $this->hierarchyService()->applyTaskScope($query, $this->membership($request));
    }

    private function ensureTaskVisible(Request $request, Task $task): void
    {
        $membership = $this->membership($request);

        abort_if((int) $task->enterprise_id !== (int) $membership->enterprise_id, 404);

        abort_if(
            !$this->hierarchyService()->taskVisible($task, $membership),
            403,
            'This task is outside your scope.'
        );
    }

    private function canAccessLead(Lead $lead, EnterpriseMembership $membership): bool
    {
        return $this->hierarchyService()->leadVisible($lead, $membership);
    }

    private function notify(int $userId, string $type, string $title, string $message, Task $task): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $task->id,
            'related_type' => 'task',
        ]);
    }
}
