<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\PlatformAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    public function index(Request $request)
    {
        $query = PlatformAuditLog::with('admin:id,name,email');

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($adminId = $request->input('admin_id')) {
            $query->where('admin_id', $adminId);
        }

        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        $paginator = $query->latest('id')->paginate((int) $request->input('per_page', 20));

        $paginator->getCollection()->transform(fn (PlatformAuditLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'meta' => $log->meta,
            'ip_address' => $log->ip_address,
            'admin' => $log->admin ? [
                'id' => $log->admin->id,
                'name' => $log->admin->name,
                'email' => $log->admin->email,
            ] : null,
            'created_at' => $log->created_at?->toIso8601String(),
        ]);

        return $this->paginated($paginator);
    }
}
