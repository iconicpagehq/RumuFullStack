<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Resources\EnterpriseAuditLogResource;
use App\Models\EnterpriseAuditLog;
use Illuminate\Http\Request;

class EnterpriseAuditLogController extends EnterpriseApiController
{
    public function index(Request $request)
    {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);

        $query = EnterpriseAuditLog::where('enterprise_id', $enterprise->id)->with('user');

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "{$action}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return $this->paginated(EnterpriseAuditLogResource::collection(
            $query->latest('id')->paginate((int) $request->input('per_page', 30))
        ));
    }
}
