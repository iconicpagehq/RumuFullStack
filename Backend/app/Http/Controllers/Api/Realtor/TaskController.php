<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Models\Lead;
use App\Models\RealtorTask;
use Illuminate\Http\Request;

class TaskController extends ApiController
{
    public function index(Request $request)
    {
        $query = RealtorTask::forUser($request->user()->id)
            ->with(['lead:id,name,phone']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn ($lead) => $lead->where('name', 'like', "%{$search}%"));
            });
        }

        if ($leadId = $request->input('lead_id')) {
            $query->where('lead_id', $leadId);
        }

        if ($request->boolean('overdue')) {
            $query->where('status', '!=', RealtorTask::STATUS_COMPLETED)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now());
        }

        $statuses = RealtorTask::forUser($request->user()->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $priorities = RealtorTask::forUser($request->user()->id)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $paginator = $query->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (RealtorTask $task) => $this->present($task)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'status_counts' => (object) $statuses->all(),
                'priority_counts' => (object) $priorities->all(),
                'overdue_count' => RealtorTask::forUser($request->user()->id)
                    ->where('status', '!=', RealtorTask::STATUS_COMPLETED)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->count(),
                'due_today_count' => RealtorTask::forUser($request->user()->id)
                    ->where('status', '!=', RealtorTask::STATUS_COMPLETED)
                    ->whereDate('due_at', today())
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTask($request);

        $task = RealtorTask::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->success($this->present($task->load('lead:id,name,phone')), 'Task created.', 201);
    }

    public function show(Request $request, RealtorTask $task)
    {
        $this->ensureOwned($request, $task);

        return $this->success($this->present($task->load('lead:id,name,phone')));
    }

    public function update(Request $request, RealtorTask $task)
    {
        $this->ensureOwned($request, $task);

        $validated = $this->validateTask($request);
        $task->update($validated);

        return $this->success($this->present($task->fresh()->load('lead:id,name,phone')), 'Task updated.');
    }

    public function updateStatus(Request $request, RealtorTask $task)
    {
        $this->ensureOwned($request, $task);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', RealtorTask::STATUSES),
            'completion_notes' => 'nullable|string|max:2000',
        ]);

        $status = $validated['status'];
        $task->update([
            'status' => $status,
            'completion_notes' => $validated['completion_notes'] ?? $task->completion_notes,
            'completed_at' => $status === RealtorTask::STATUS_COMPLETED ? now() : null,
        ]);

        return $this->success($this->present($task->fresh()->load('lead:id,name,phone')), 'Task status updated.');
    }

    public function destroy(Request $request, RealtorTask $task)
    {
        $this->ensureOwned($request, $task);
        $task->delete();

        return $this->success(null, 'Task deleted.');
    }

    private function validateTask(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'required|in:' . implode(',', RealtorTask::PRIORITIES),
            'due_at' => 'nullable|date',
            'status' => 'sometimes|in:' . implode(',', RealtorTask::STATUSES),
            'lead_id' => 'nullable|integer',
        ]);

        if (!empty($validated['lead_id'])) {
            $ownsLead = Lead::whereNull('enterprise_id')
                ->forRealtor($request->user()->id)
                ->whereKey($validated['lead_id'])
                ->exists();

            if (!$ownsLead) {
                abort(404);
            }
        }

        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['status'] = $validated['status'] ?? RealtorTask::STATUS_PENDING;

        return $validated;
    }

    private function ensureOwned(Request $request, RealtorTask $task): void
    {
        abort_if((int) $task->user_id !== (int) $request->user()->id, 404);
    }

    private function present(RealtorTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_at' => $task->due_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'completion_notes' => $task->completion_notes,
            'is_overdue' => $task->isOverdue(),
            'lead' => $task->relationLoaded('lead') && $task->lead ? [
                'id' => $task->lead->id,
                'name' => $task->lead->name,
                'phone' => $task->lead->phone,
            ] : null,
            'created_at' => $task->created_at?->toIso8601String(),
        ];
    }
}
