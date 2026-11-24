<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Jobs\SendAdminBroadcastJob;
use App\Models\AdminBroadcast;
use App\Models\EnterpriseMembership;
use App\Models\User;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BroadcastController extends ApiController
{
    public function index()
    {
        $broadcasts = AdminBroadcast::with(['admin:id,name', 'enterprise:id,name'])
            ->latest('id')
            ->paginate(20);

        $broadcasts->getCollection()->transform(fn (AdminBroadcast $broadcast) => $this->present($broadcast));

        return $this->paginated($broadcasts);
    }

    public function preview(Request $request)
    {
        $validated = $this->validatePayload($request);

        return $this->success([
            'recipients_count' => $this->recipientQuery($validated)->distinct()->count('users.id'),
        ]);
    }

    public function store(Request $request, PlatformAuditService $audit)
    {
        $validated = $this->validatePayload($request);

        $recipients = $this->recipientQuery($validated)->distinct()->count('users.id');

        $broadcast = AdminBroadcast::create([
            'admin_id' => $request->user()->id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'audience' => $validated['audience'],
            'enterprise_id' => $validated['audience'] === AdminBroadcast::AUDIENCE_ENTERPRISE
                ? $validated['enterprise_id']
                : null,
            'recipients_count' => $recipients,
            'status' => AdminBroadcast::STATUS_QUEUED,
        ]);

        SendAdminBroadcastJob::dispatch($broadcast);

        $audit->log($request->user(), 'broadcast.sent', $broadcast, 'Platform broadcast queued', [
            'audience' => $broadcast->audience,
            'recipients' => $recipients,
        ]);

        return $this->success($this->present($broadcast->fresh(['admin', 'enterprise'])), 'Broadcast queued.', 201);
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
            'audience' => 'required|in:' . implode(',', AdminBroadcast::AUDIENCES),
            'enterprise_id' => 'nullable|integer|exists:enterprises,id',
        ]);

        if ($validated['audience'] === AdminBroadcast::AUDIENCE_ENTERPRISE && empty($validated['enterprise_id'])) {
            throw ValidationException::withMessages([
                'enterprise_id' => ['Select the enterprise that should receive this broadcast.'],
            ]);
        }

        return $validated;
    }

    private function recipientQuery(array $validated)
    {
        $query = User::query()->select('users.id');

        return match ($validated['audience']) {
            AdminBroadcast::AUDIENCE_ALL_REALTORS => $query
                ->where('users.role', 'realtor')
                ->whereNull('users.subscription_suspended_at'),
            AdminBroadcast::AUDIENCE_ENTERPRISE_ADMINS => $query
                ->where('users.role', 'enterprise')
                ->whereHas('enterpriseMembership', fn ($membership) => $membership
                    ->where('role', EnterpriseMembership::ROLE_ADMIN)
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->whereHas('enterprise', fn ($enterprise) => $enterprise->where('status', 'active'))),
            AdminBroadcast::AUDIENCE_ENTERPRISE_MEMBERS => $query
                ->where('users.role', 'enterprise')
                ->whereHas('enterpriseMembership', fn ($membership) => $membership
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->whereHas('enterprise', fn ($enterprise) => $enterprise->where('status', 'active'))),
            AdminBroadcast::AUDIENCE_ENTERPRISE => $query
                ->where('users.role', 'enterprise')
                ->whereHas('enterpriseMembership', fn ($membership) => $membership
                    ->where('enterprise_id', $validated['enterprise_id'])
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->whereHas('enterprise', fn ($enterprise) => $enterprise->where('status', 'active'))),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function present(AdminBroadcast $broadcast): array
    {
        return [
            'id' => $broadcast->id,
            'title' => $broadcast->title,
            'message' => $broadcast->message,
            'audience' => $broadcast->audience,
            'enterprise' => $broadcast->enterprise ? [
                'id' => $broadcast->enterprise->id,
                'name' => $broadcast->enterprise->name,
            ] : null,
            'recipients_count' => $broadcast->recipients_count,
            'status' => $broadcast->status,
            'sent_at' => $broadcast->sent_at?->toIso8601String(),
            'admin' => $broadcast->admin?->name,
            'created_at' => $broadcast->created_at?->toIso8601String(),
        ];
    }
}
