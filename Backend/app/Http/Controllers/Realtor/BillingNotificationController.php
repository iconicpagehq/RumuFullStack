<?php

namespace App\Http\Controllers\Realtor;

use App\Http\Controllers\Controller;
use App\Models\BillingNotification;
use Illuminate\Http\Request;

class BillingNotificationController extends Controller
{
    public function index()
    {
        $notifications = BillingNotification::where('user_id', auth()->id())
            ->whereIn('channel', ['in_app', 'both'])
            ->with(['subscription', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('realtor.billing.notifications', compact('notifications'));
    }

    public function markAsRead(BillingNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }
}
