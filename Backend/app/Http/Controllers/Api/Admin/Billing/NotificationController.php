<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BillingNotificationResource;
use App\Models\BillingNotification;
use App\Services\Billing\BillingNotificationService;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $query = BillingNotification::with(['user', 'subscription', 'invoice']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $this->paginated(BillingNotificationResource::collection(
            $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function resend(BillingNotification $notification, BillingNotificationService $service)
    {
        if ($notification->status === 'sent') {
            return $this->error('Notification was already sent successfully.', 409);
        }

        try {
            $service->notify(
                $notification->user,
                $notification->type,
                $notification->subscription,
                $notification->payment,
                $notification->invoice,
                $notification->channel
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to resend notification.', 500);
        }

        return $this->success(null, 'Notification resent successfully.');
    }
}
