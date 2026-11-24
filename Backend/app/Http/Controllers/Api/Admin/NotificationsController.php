<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationsController extends ApiController
{
    public function index(Request $request)
    {
        return $this->paginated(NotificationResource::collection(
            $request->user()->notifications()->latest()->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function unreadCount(Request $request)
    {
        return $this->success([
            'count' => $request->user()->notifications()->unread()->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Forbidden.', 403);
        }

        $notification->markAsRead();

        return $this->success(new NotificationResource($notification->fresh()), 'Notification marked as read.');
    }

    public function markAllRead(Request $request)
    {
        $request->user()->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this->success(['unread_count' => 0], 'All notifications marked as read.');
    }
}
