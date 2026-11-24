<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user()->load('plan');

        return $this->success([
            'user' => new UserResource($user),
            'options' => [
                'timezones' => [
                    'Asia/Kolkata' => 'India (IST)',
                    'America/New_York' => 'New York (EST)',
                    'America/Chicago' => 'Chicago (CST)',
                    'America/Denver' => 'Denver (MST)',
                    'America/Los_Angeles' => 'Los Angeles (PST)',
                    'Europe/London' => 'London (GMT)',
                    'Europe/Paris' => 'Paris (CET)',
                    'Europe/Berlin' => 'Berlin (CET)',
                    'Asia/Dubai' => 'Dubai (GST)',
                    'Asia/Singapore' => 'Singapore (SGT)',
                    'Asia/Tokyo' => 'Tokyo (JST)',
                    'Australia/Sydney' => 'Sydney (AEST)',
                ],
                'languages' => [
                    'en' => 'English',
                    'hi' => 'Hindi',
                    'mr' => 'Marathi',
                    'es' => 'Spanish',
                    'fr' => 'French',
                    'de' => 'German',
                    'zh' => 'Chinese',
                    'ja' => 'Japanese',
                ],
                'themes' => ['light', 'dark', 'auto'],
            ],
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
        ]);

        $request->user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ]);

        return $this->success(
            ['user' => new UserResource($request->user()->fresh()->load('plan'))],
            'Notification settings updated.'
        );
    }

    public function updatePrivacy(Request $request)
    {
        $request->validate([
            'public_profile' => 'boolean',
        ]);

        $request->user()->update([
            'public_profile' => $request->boolean('public_profile'),
        ]);

        return $this->success(
            ['user' => new UserResource($request->user()->fresh()->load('plan'))],
            'Privacy settings updated.'
        );
    }

    public function updateAppearance(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,auto',
            'language' => 'required|string|max:5',
            'timezone' => 'required|string|max:50',
        ]);

        $request->user()->update($validated);

        return $this->success(
            ['user' => new UserResource($request->user()->fresh()->load('plan'))],
            'Appearance settings updated.'
        );
    }

    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:DELETE',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password)) {
            return $this->error('The provided password is incorrect.', 422, [
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->success(null, 'Your account has been deleted.');
    }
}
