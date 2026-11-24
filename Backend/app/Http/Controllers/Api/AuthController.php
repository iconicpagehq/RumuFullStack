<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Mail\PasswordResetOtpMail;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $basicPlan = $this->getOrCreateDefaultPlan();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'realtor',
            'plan_id' => $basicPlan->id,
        ]);

        $this->notifyAdminsRealtorRegistered($user);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('plan')),
        ], 'Registration successful.', 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (!in_array($user->role, ['realtor', 'admin', 'enterprise'], true)) {
            return $this->error('This account cannot sign in.', 403);
        }

        if ($user->role === 'enterprise') {
            $membership = $user->activeEnterpriseMembership();
            if (!$membership) {
                return $this->error('Your enterprise workspace is unavailable. Contact your company administrator.', 403);
            }
        }

        $token = $user->createToken('spa')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($this->loadAuthContext($user)),
        ], 'Login successful.');
    }

    public function me(Request $request)
    {
        $user = $this->loadAuthContext($request->user());

        $user->loadCount([
            'notifications as unread_notifications_count' => fn ($query) => $query->where('is_read', false),
        ]);

        return $this->success(new UserResource($user));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->success(null, 'All sessions revoked.');
    }

    public function sendPasswordResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.'],
            ]);
        }

        // Generate 6-digit OTP
        $otp = sprintf('%06d', random_int(100000, 999999));

        // Store hashed OTP in password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // Send email
        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($user->name, $otp));
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset OTP email', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            if (app()->environment('production')) {
                return $this->error('Failed to send verification email. Please check your email or try again later.', 500);
            }
        }

        $responsePayload = [
            'email' => $user->email,
            'expires_in_minutes' => 15,
        ];

        if (!app()->environment('production')) {
            $responsePayload['debug_otp'] = $otp;
        }

        return $this->success(
            $responsePayload,
            'Verification code sent to your email.'
        );
    }

    public function verifyPasswordResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'otp' => ['No verification code requested for this email.'],
            ]);
        }

        if (now()->subMinutes(15)->gt($record->created_at)) {
            throw ValidationException::withMessages([
                'otp' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        if (!Hash::check($validated['otp'], $record->token)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid verification code. Please check and try again.'],
            ]);
        }

        return $this->success([
            'email' => $validated['email'],
            'verified' => true,
        ], 'Code verified successfully.');
    }

    public function resetPasswordWithOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$record || now()->subMinutes(15)->gt($record->created_at) || !Hash::check($validated['otp'], $record->token)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired verification code.'],
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found for this email address.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke existing sessions/tokens
        $user->tokens()->delete();

        // Delete token record
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return $this->success(null, 'Password has been reset successfully. Please sign in with your new password.');
    }

    private function getOrCreateDefaultPlan(): Plan
    {
        $basicPlan = Plan::where('is_default', true)->where('is_active', true)->first()
            ?? Plan::active()->orderBy('id')->first();

        if (!$basicPlan) {
            $basicPlan = Plan::create([
                'key' => 'basic',
                'name' => 'Basic',
                'max_campaigns' => 5,
                'max_leads_per_campaign' => 100,
                'is_default' => true,
                'is_active' => true,
                'price' => 0.00,
            ]);
        }

        return $basicPlan;
    }

    private function loadAuthContext(User $user): User
    {
        $relations = ['plan'];

        if ($user->role === 'enterprise') {
            $relations[] = 'enterpriseMembership.enterprise.plan';
            $relations[] = 'enterpriseMembership.primaryTeamMembership.team';
        }

        return $user->load($relations);
    }

    private function notifyAdminsRealtorRegistered(User $user): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'realtor_registered',
                'title' => 'New Realtor Registered',
                'message' => "{$user->name} ({$user->email}) has registered as a new realtor.",
                'related_id' => $user->id,
                'related_type' => 'user',
            ]);
        }
    }
}
