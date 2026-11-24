<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingNotification;
use App\Services\Billing\BillingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = BillingNotification::with(['user', 'subscription', 'invoice']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.billing.notifications.index', compact('notifications'));
    }

    public function resend(BillingNotification $notification, BillingNotificationService $service)
    {
        if ($notification->status === 'sent') {
            return back()->with('error', 'Notification was already sent successfully.');
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

            // Log the resend action
            Log::info("Admin manually resent billing notification ID: {$notification->id}");

            return back()->with('success', 'Notification resent successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to resend notification.');
        }
    }
}
