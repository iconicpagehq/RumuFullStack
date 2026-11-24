<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\EnterpriseSeatRequest;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatRequestsController extends ApiController
{
    public function index(Request $request)
    {
        $query = EnterpriseSeatRequest::with(['enterprise:id,name', 'requester:id,name,email', 'approver:id,name'])
            ->latest('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($enterpriseId = $request->input('enterprise_id')) {
            $query->where('enterprise_id', $enterpriseId);
        }

        $paginator = $query->paginate((int) $request->input('per_page', 20));

        $paginator->getCollection()->transform(fn (EnterpriseSeatRequest $seatRequest) => [
            'id' => $seatRequest->id,
            'enterprise' => $seatRequest->enterprise ? [
                'id' => $seatRequest->enterprise->id,
                'name' => $seatRequest->enterprise->name,
            ] : null,
            'seat_type' => $seatRequest->seat_type,
            'seat_label' => $seatRequest->seatLabel(),
            'quantity' => $seatRequest->quantity,
            'status' => $seatRequest->status,
            'note' => $seatRequest->note,
            'admin_notes' => $seatRequest->admin_notes,
            'requested_by' => $seatRequest->requester ? [
                'id' => $seatRequest->requester->id,
                'name' => $seatRequest->requester->name,
                'email' => $seatRequest->requester->email,
            ] : null,
            'approved_by' => $seatRequest->approver?->name,
            'approved_at' => $seatRequest->approved_at?->toIso8601String(),
            'created_at' => $seatRequest->created_at?->toIso8601String(),
        ]);

        return $this->paginated($paginator);
    }

    public function approve(Request $request, EnterpriseSeatRequest $seatRequest, PlatformAuditService $audit)
    {
        if ($seatRequest->status !== EnterpriseSeatRequest::STATUS_PENDING) {
            return $this->error('This seat request has already been processed.', 409);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($seatRequest, $validated, $request, $audit) {
            $enterprise = $seatRequest->enterprise;
            $column = $seatRequest->seat_type === EnterpriseSeatRequest::SEAT_MANAGERS
                ? 'extra_managers'
                : 'extra_employees';

            $enterprise->increment($column, $seatRequest->quantity);

            $seatRequest->update([
                'status' => EnterpriseSeatRequest::STATUS_APPROVED,
                'admin_notes' => $validated['notes'] ?? null,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $audit->log($request->user(), 'enterprise.seat_request_approved', $seatRequest, 'Enterprise seat add-on approved', [
                'enterprise_id' => $enterprise->id,
                'seat_type' => $seatRequest->seat_type,
                'quantity' => $seatRequest->quantity,
            ]);
        });

        return $this->success(null, 'Seat request approved.');
    }

    public function reject(Request $request, EnterpriseSeatRequest $seatRequest, PlatformAuditService $audit)
    {
        if ($seatRequest->status !== EnterpriseSeatRequest::STATUS_PENDING) {
            return $this->error('This seat request has already been processed.', 409);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $seatRequest->update([
            'status' => EnterpriseSeatRequest::STATUS_REJECTED,
            'admin_notes' => $validated['reason'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $audit->log($request->user(), 'enterprise.seat_request_rejected', $seatRequest, 'Enterprise seat add-on rejected', [
            'enterprise_id' => $seatRequest->enterprise_id,
            'reason' => $validated['reason'],
        ]);

        return $this->success(null, 'Seat request rejected.');
    }
}
