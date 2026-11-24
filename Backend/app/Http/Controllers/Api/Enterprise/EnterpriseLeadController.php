<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseLeadResource;
use App\Models\Lead;
use App\Models\Team;
use App\Services\EnterpriseLeadCsvImportService;
use App\Services\EnterpriseLeadService;
use App\Services\LeadImportFileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnterpriseLeadController extends EnterpriseApiController
{
    public function __construct(
        private EnterpriseLeadService $leads,
        private EnterpriseLeadCsvImportService $csv,
        private LeadImportFileService $files,
    ) {}

    public function index(Request $request)
    {
        $membership = $this->membership($request);

        $query = $this->leads->visibleQuery($membership)
            ->with(['assignee', 'team', 'project', 'inventoryUnit', 'campaign'])
            ->withCount(['tasks as open_tasks_count' => fn ($inner) => $inner->whereNotIn('status', ['completed', 'cancelled'])]);

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($stage = $request->input('stage')) {
            $stages = is_array($stage) ? $stage : explode(',', $stage);
            $query->whereIn('enterprise_stage', array_intersect($stages, Lead::ENTERPRISE_STAGES));
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('team_id', $teamId);
        }

        if ($campaignId = $request->input('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('inventory_project_id', $projectId);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_to');
        }

        if ($request->boolean('overdue')) {
            $query->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', now())
                ->whereNotIn('enterprise_stage', ['won', 'lost']);
        }

        if ($request->boolean('follow_up_today')) {
            $query->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()]);
        }

        match ($request->input('sort')) {
            'follow_up' => $query->orderByRaw('next_follow_up_at IS NULL')->orderBy('next_follow_up_at'),
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $paginator = $query->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => EnterpriseLeadResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => null,
        ]);
    }

    public function importCsv(Request $request)
    {
        $this->requireRole($request, ['admin', 'manager']);

        $validated = $request->validate([
            'csv_file' => 'required|file|' . LeadImportFileService::VALIDATION_RULE . '|max:2048',
            'team_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
        ]);

        $result = $this->csv->import(
            $this->enterprise($request),
            $request->user(),
            $this->membership($request),
            $request->file('csv_file'),
            isset($validated['team_id']) ? (int) $validated['team_id'] : null,
            isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null,
        );

        if ($result['imported'] === 0) {
            return $this->error($result['message'], 422, $result);
        }

        return $this->success($result, $result['message']);
    }

    public function downloadSampleCsv(Request $request)
    {
        $this->requireRole($request, ['admin', 'manager']);

        $rows = [
            ['Name', 'Email', 'Phone', 'Company'],
            ['John Doe', 'john@example.com', '9876543210', 'Acme Inc'],
            ['Jane Smith', 'jane@example.com', '9123456780', 'Example Co'],
        ];

        return $this->files->sampleDownload(
            $rows,
            'leads-sample-enterprise',
            $request->query('format')
        );
    }

    public function store(Request $request)
    {
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|in:' . implode(',', Lead::ENTERPRISE_SOURCES),
            'priority' => 'nullable|in:low,medium,high,urgent',
            'team_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'inventory_project_id' => 'nullable|integer',
            'inventory_unit_id' => 'nullable|integer',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0|gte:budget_min',
            'preferred_location' => 'nullable|string|max:255',
            'preferred_configuration' => 'nullable|string|max:100',
            'next_follow_up_at' => 'nullable|date',
            'note' => 'nullable|string|max:5000',
            'custom_fields' => 'nullable|array',
            'allow_duplicate' => 'sometimes|boolean',
        ]);

        if (!$request->filled('email') && !$request->filled('phone')) {
            throw ValidationException::withMessages([
                'phone' => ['Provide at least an email or phone number.'],
            ]);
        }

        if ($membership->isEmployee()) {
            $validated['assigned_to'] = $membership->user_id;
            $validated['team_id'] = $membership->primaryTeamMembership?->team_id;
        } elseif ($membership->isManager()) {
            $managedTeam = $membership->primaryTeamMembership?->team_id;
            if (empty($validated['team_id']) && $managedTeam) {
                $validated['team_id'] = $managedTeam;
            }
        }

        $lead = $this->leads->create($enterprise, $request->user(), $membership, $validated);

        $lead->load(['assignee', 'team', 'project', 'inventoryUnit', 'campaign']);

        return $this->success(new EnterpriseLeadResource($lead), 'Lead created.', 201);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->ensureLeadAccess($request, $lead);

        $lead->load([
            'assignee', 'assignedManager', 'team', 'project', 'inventoryUnit', 'campaign',
            'activities.user', 'tasks.assignee', 'tasks.creator', 'siteVisits.assignee', 'siteVisits.project',
        ]);

        return $this->success(new EnterpriseLeadResource($lead));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->ensureLeadAccess($request, $lead);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:255',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'source' => 'sometimes|in:' . implode(',', Lead::ENTERPRISE_SOURCES),
            'team_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'inventory_project_id' => 'nullable|integer',
            'inventory_unit_id' => 'nullable|integer',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'preferred_location' => 'nullable|string|max:255',
            'preferred_configuration' => 'nullable|string|max:100',
            'next_follow_up_at' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ]);

        $lead = $this->leads->updateDetails(
            $lead,
            $this->enterprise($request),
            $request->user(),
            $this->membership($request),
            $validated
        );

        $lead->load(['assignee', 'team', 'project', 'inventoryUnit', 'campaign']);

        return $this->success(new EnterpriseLeadResource($lead), 'Lead updated.');
    }

    public function destroy(Request $request, Lead $lead)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $this->ensureLeadAccess($request, $lead);

        $this->leads->delete($lead, $this->enterprise($request), $request->user());

        return $this->success(null, 'Lead deleted.');
    }

    public function updateStage(Request $request, Lead $lead)
    {
        $this->ensureLeadAccess($request, $lead);

        $validated = $request->validate([
            'stage' => 'required|in:' . implode(',', Lead::ENTERPRISE_STAGES),
            'note' => 'nullable|string|max:5000',
            'deal_value' => 'nullable|numeric|min:0',
            'lost_reason' => 'nullable|string|max:1000',
            'next_follow_up_at' => 'nullable|date',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'preferred_location' => 'nullable|string|max:255',
            'preferred_configuration' => 'nullable|string|max:100',
        ]);

        $lead = $this->leads->updateStage(
            $lead,
            $this->enterprise($request),
            $request->user(),
            $this->membership($request),
            $validated['stage'],
            $validated
        );

        return $this->success(new EnterpriseLeadResource($lead->load(['assignee', 'team', 'activities.user'])), 'Pipeline stage updated.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $membership = $this->membership($request);

        if (!$membership->isAdmin() && !$membership->isManager()
            && !($membership->isEmployee() && $this->hierarchyService()->hasReports($membership))) {
            abort(403, 'You do not have permission to assign leads.');
        }

        $this->ensureLeadAccess($request, $lead);

        $validated = $request->validate([
            'assigned_to' => 'required|integer',
            'team_id' => 'nullable|integer',
        ]);

        $team = null;
        if (!empty($validated['team_id'])) {
            $team = \App\Models\Team::where('enterprise_id', $this->enterprise($request)->id)->find($validated['team_id']);
            if (!$team) {
                throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
            }
        }

        $lead = $this->leads->assign(
            $lead,
            $this->enterprise($request),
            $request->user(),
            $this->membership($request),
            (int) $validated['assigned_to'],
            $team
        );

        return $this->success(new EnterpriseLeadResource($lead->load(['assignee', 'team'])), 'Lead assignment updated.');
    }

    public function roundRobin(Request $request, Lead $lead)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $this->ensureLeadAccess($request, $lead);

        $validated = $request->validate(['team_id' => 'nullable|integer']);
        $membership = $this->membership($request);

        if ($membership->isManager()) {
            $team = \App\Models\Team::where('enterprise_id', $this->enterprise($request)->id)
                ->where('manager_id', $membership->user_id)
                ->first();
        } else {
            $team = !empty($validated['team_id'])
                ? \App\Models\Team::where('enterprise_id', $this->enterprise($request)->id)->find($validated['team_id'])
                : ($lead->team_id ? \App\Models\Team::where('enterprise_id', $this->enterprise($request)->id)->find($lead->team_id) : null);
        }

        if (!$team) {
            throw ValidationException::withMessages(['team_id' => ['Select a team for round-robin assignment.']]);
        }

        $lead = $this->leads->roundRobinAssign($lead, $team, $request->user(), $membership);

        if (!$lead) {
            return $this->error('No eligible team members are available for round-robin assignment.', 422);
        }

        return $this->success(new EnterpriseLeadResource($lead->load(['assignee', 'team'])), 'Lead assigned by round robin.');
    }

    public function distribute(Request $request)
    {
        $this->requireRole($request, ['admin', 'manager']);
        $membership = $this->membership($request);
        $enterprise = $this->enterprise($request);

        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1|max:100',
            'lead_ids.*' => 'integer|distinct',
            'team_id' => 'required|integer',
            'mode' => 'required|in:round_robin,manual',
            'assigned_to' => 'nullable|required_if:mode,manual|integer',
        ]);

        $team = Team::where('enterprise_id', $enterprise->id)->find($validated['team_id']);
        if (!$team) {
            throw ValidationException::withMessages(['team_id' => ['Select a team from this enterprise.']]);
        }

        $result = $this->leads->distribute(
            $enterprise,
            $request->user(),
            $membership,
            $team,
            array_map('intval', $validated['lead_ids']),
            $validated['mode'],
            isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
        );

        $count = $result['assigned_count'];

        return $this->success(
            $result,
            $count . ' ' . ($count === 1 ? 'lead distributed.' : 'leads distributed.')
        );
    }

    public function addNote(Request $request, Lead $lead)
    {
        $this->ensureLeadAccess($request, $lead);

        $validated = $request->validate(['note' => 'required|string|max:5000']);

        $activity = $this->leads->addNote($lead, $request->user(), $validated['note']);
        $activity->load('user');

        return $this->success(new \App\Http\Resources\LeadActivityResource($activity), 'Note added.', 201);
    }

    private function ensureLeadAccess(Request $request, Lead $lead): void
    {
        abort_if($lead->enterprise_id !== $this->enterprise($request)->id, 404);

        if (!$this->leads->canAccess($lead, $this->membership($request))) {
            abort(403, 'This lead is outside your scope.');
        }
    }
}
