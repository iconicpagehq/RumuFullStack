<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BillingNotificationResource;
use App\Models\BillingNotification;
use Illuminate\Http\Request;

class BillingNotificationController extends ApiController
{
    public function index(Request $request)
    {
        $query = BillingNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('channel', ['in_app', 'both'])
            ->with(['subscription', 'invoice', 'payment'])
            ->orderBy('created_at', 'desc');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $notifications = $query->paginate(15);
        $payload = BillingNotificationResource::collection($notifications)->response()->getData(true);

        return response()->json([
            'success' => true,
            'data' => $payload['data'] ?? [],
            'meta' => array_merge($payload['meta'] ?? [], [
                'unread_count' => $this->unreadCountFor($request->user()->id),
            ]),
            'message' => null,
        ]);
    }

    public function unreadCount(Request $request)
    {
        return $this->success(['unread_count' => $this->unreadCountFor($request->user()->id)]);
    }

    public function markAsRead(Request $request, BillingNotification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('You are not allowed to update this notification.', 403);
        }

        $notification->update(['read_at' => now()]);

        return $this->success(
            new BillingNotificationResource($notification->load(['subscription', 'invoice', 'payment'])),
            'Notification marked as read.'
        );
    }

    public function markAllAsRead(Request $request)
    {
        $updated = BillingNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(['updated' => $updated], 'Notifications marked as read.');
    }

    private function unreadCountFor(int $userId): int
    {
        return BillingNotification::where('user_id', $userId)
            ->whereIn('channel', ['in_app', 'both'])
            ->whereNull('read_at')
            ->count();
    }
}
