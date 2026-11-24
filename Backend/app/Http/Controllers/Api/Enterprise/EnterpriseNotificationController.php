<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Controllers\Api\ApiController;
use App\Models\Notification;
use Illuminate\Http\Request;

class EnterpriseNotificationController extends ApiController
{
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)->latest();

        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $paginator = $query->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request)
    {
        return $this->success([
            'count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id, 404);
        $notification->markAsRead();

        return $this->success(null, 'Notification read.');
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }
}
