<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends ApiController
{
    public function show(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_campaigns' => $user->campaigns()->count(),
            'total_leads' => $user->campaigns()->withCount('leads')->get()->sum('leads_count'),
            'active_campaigns' => $user->campaigns()->where('status', 'active')->count(),
            'won_leads' => $user->campaigns()->withCount(['leads' => function ($query) {
                $query->where('status', 'won');
            }])->get()->sum('leads_count'),
        ];

        return $this->success([
            'user' => new UserResource($user->load('plan')),
            'stats' => $stats,
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update([
            'avatar' => $validated['avatar']->store('avatars', 'public'),
        ]);

        return $this->success(new UserResource($user->fresh()->load('plan')), 'Avatar updated.');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update($validated);

        return $this->success(new UserResource($user->fresh()->load('plan')), 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $this->success(null, 'Password updated.');
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['boolean'],
            'marketing_emails' => ['boolean'],
        ]);

        $request->user()->update([
            'email_notifications' => $validated['email_notifications'] ?? false,
            'marketing_emails' => $validated['marketing_emails'] ?? false,
        ]);

        return $this->success(new UserResource($request->user()->fresh()->load('plan')), 'Notification settings updated.');
    }
}
