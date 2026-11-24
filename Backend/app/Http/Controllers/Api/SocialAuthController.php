<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\MetaSyncLog;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends ApiController
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirectUrl(Request $request, string $provider)
    {
        if (!$this->providerIsSupported($provider)) {
            return $this->error('Unsupported provider.', 422);
        }

        $redirectUri = $request->query('redirect_uri');

        try {
            $driver = Socialite::driver($provider)->stateless();

            if ($redirectUri) {
                $driver->redirectUrl($redirectUri);
            }

            return $this->success([
                'url' => $driver->redirect()->getTargetUrl(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Social redirect URL failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->error('Social login is not configured for this provider.', 503);
        }
    }

    public function login(Request $request, string $provider)
    {
        if (!$this->providerIsSupported($provider)) {
            return $this->error('Unsupported provider.', 422);
        }

        try {
            $profile = $this->resolveProfile($request, $provider);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Social login failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->error($e->getMessage() ?: 'Social sign-in failed. Please try again.', 422);
        }

        if (empty($profile['email'])) {
            return $this->error(ucfirst($provider) . ' account did not return an email address.', 422);
        }

        $providerId = (string) $profile['id'];
        $email = strtolower($profile['email']);

        $user = User::where($this->providerColumn($provider), $providerId)->first();
        $emailOwner = User::where('email', $email)->first();

        if ($user && $emailOwner && $user->id !== $emailOwner->id) {
            return $this->error('This social account is already linked to another user.', 409);
        }

        if (!$user) {
            $user = $emailOwner;
        }

        $isNew = false;

        if ($user) {
            if (empty($user->{$this->providerColumn($provider)})) {
                $user->{$this->providerColumn($provider)} = $providerId;
            }

            if (empty($user->{$this->providerAvatarColumn($provider)}) && !empty($profile['avatar'])) {
                $user->{$this->providerAvatarColumn($provider)} = $profile['avatar'];
            }

            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();
        } else {
            $plan = $this->getOrCreateDefaultPlan();

            $user = User::create([
                'name' => $profile['name'] ?: 'User',
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'realtor',
                'plan_id' => $plan->id,
                'email_verified_at' => now(),
                $this->providerColumn($provider) => $providerId,
                $this->providerAvatarColumn($provider) => $profile['avatar'] ?? null,
            ]);

            $isNew = true;
            $this->notifyAdminsRealtorRegistered($user);
        }

        if (!in_array($user->role, ['realtor', 'admin', 'enterprise'], true)) {
            return $this->error('This account cannot sign in.', 403);
        }

        if ($user->role === 'enterprise' && !$user->activeEnterpriseMembership()) {
            return $this->error('Your enterprise workspace is unavailable. Contact your company administrator.', 403);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => $isNew,
            'user' => $this->userPayload($user),
        ], 'Signed in with ' . ucfirst($provider) . '.');
    }

    public function link(Request $request, string $provider)
    {
        if (!$this->providerIsSupported($provider)) {
            return $this->error('Unsupported provider.', 422);
        }

        $user = $request->user();

        try {
            $profile = $this->resolveProfile($request, $provider);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Social link failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->error('Failed to connect ' . ucfirst($provider) . ' account.', 422);
        }

        $providerId = (string) $profile['id'];

        $owner = User::where($this->providerColumn($provider), $providerId)->first();
        if ($owner && $owner->id !== $user->id) {
            return $this->error('This ' . ucfirst($provider) . ' account is already linked to another user.', 409);
        }

        if (!empty($profile['email'])) {
            $emailOwner = User::where('email', strtolower($profile['email']))->first();
            if ($emailOwner && $emailOwner->id !== $user->id) {
                return $this->error('This social account email is already used by another user.', 409);
            }
        }

        $user->{$this->providerColumn($provider)} = $providerId;
        $user->{$this->providerAvatarColumn($provider)} = $profile['avatar'] ?? $user->{$this->providerAvatarColumn($provider)};

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        if ($provider === 'facebook') {
            MetaSyncLog::create([
                'user_id' => $user->id,
                'campaign_id' => null,
                'type' => 'success',
                'event_type' => 'connected',
                'message' => 'Facebook login account linked.',
                'context' => ['facebook_id' => $providerId],
            ]);
        }

        return $this->success($this->userPayload($user), ucfirst($provider) . ' account linked.');
    }

    public function unlink(Request $request, string $provider)
    {
        if (!$this->providerIsSupported($provider)) {
            return $this->error('Unsupported provider.', 422);
        }

        $user = $request->user();

        if ($provider === 'facebook') {
            MetaSyncLog::create([
                'user_id' => $user->id,
                'campaign_id' => null,
                'type' => 'info',
                'event_type' => 'disconnected',
                'message' => 'Facebook account disconnected. Associated pages marked as disconnected.',
                'context' => ['facebook_id' => $user->facebook_id],
            ]);

            $user->metaPages()->update(['status' => 'disconnected']);

            $user->facebook_id = null;
            $user->facebook_avatar = null;
            $user->facebook_page_id = null;
            $user->facebook_page_name = null;
            $user->facebook_page_access_token = null;
            $user->facebook_token_expired = false;
        } else {
            $user->google_id = null;
            $user->google_avatar = null;
        }

        $user->save();

        return $this->success($this->userPayload($user), ucfirst($provider) . ' account unlinked.');
    }

    /**
     * @return array{id: string, name: ?string, email: ?string, avatar: ?string}
     */
    private function resolveProfile(Request $request, string $provider): array
    {
        if ($provider === 'google' && $request->filled('id_token')) {
            return $this->googleProfileFromIdToken($request->string('id_token')->toString());
        }

        if ($request->filled('access_token')) {
            return $provider === 'google'
                ? $this->googleProfileFromAccessToken($request->string('access_token')->toString())
                : $this->facebookProfileFromAccessToken($request->string('access_token')->toString());
        }

        $request->validate([
            'code' => 'required|string',
            'redirect_uri' => 'nullable|url',
        ]);

        $driver = Socialite::driver($provider)->stateless();

        if ($request->filled('redirect_uri')) {
            $driver->redirectUrl($request->string('redirect_uri')->toString());
        }

        $socialUser = $driver->user();

        return [
            'id' => (string) $socialUser->getId(),
            'name' => $socialUser->getName() ?: $socialUser->getNickname(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
        ];
    }

    private function googleProfileFromIdToken(string $idToken): array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);

        if (!$response->successful() || $response->json('aud') !== config('services.google.client_id')) {
            throw new \RuntimeException('Invalid Google token.');
        }

        if (!$response->json('email_verified')) {
            throw new \RuntimeException('Google email is not verified.');
        }

        return [
            'id' => (string) $response->json('sub'),
            'name' => $response->json('name'),
            'email' => $response->json('email'),
            'avatar' => $response->json('picture'),
        ];
    }

    private function googleProfileFromAccessToken(string $token): array
    {
        $response = Http::withToken($token)->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (!$response->successful()) {
            throw new \RuntimeException('Invalid Google access token.');
        }

        return [
            'id' => (string) $response->json('sub'),
            'name' => $response->json('name'),
            'email' => $response->json('email'),
            'avatar' => $response->json('picture'),
        ];
    }

    private function facebookProfileFromAccessToken(string $token): array
    {
        $response = Http::get('https://graph.facebook.com/' . config('services.meta.graph_version', 'v19.0') . '/me', [
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $token,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Invalid Facebook access token.');
        }

        return [
            'id' => (string) $response->json('id'),
            'name' => $response->json('name'),
            'email' => $response->json('email'),
            'avatar' => $response->json('picture.data.url'),
        ];
    }

    private function userPayload(User $user): UserResource
    {
        $relations = ['plan'];

        if ($user->role === 'enterprise') {
            $relations[] = 'enterpriseMembership.enterprise.plan';
            $relations[] = 'enterpriseMembership.primaryTeamMembership.team';
        }

        $user->load($relations)->loadCount([
            'notifications as unread_notifications_count' => fn ($query) => $query->where('is_read', false),
        ]);

        return new UserResource($user);
    }

    private function providerIsSupported(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    private function providerColumn(string $provider): string
    {
        return $provider === 'google' ? 'google_id' : 'facebook_id';
    }

    private function providerAvatarColumn(string $provider): string
    {
        return $provider === 'google' ? 'google_avatar' : 'facebook_avatar';
    }

    private function getOrCreateDefaultPlan(): Plan
    {
        $plan = Plan::where('is_default', true)->where('is_active', true)->first()
            ?? Plan::active()->orderBy('id')->first();

        if (!$plan) {
            $plan = Plan::create([
                'key' => 'basic',
                'name' => 'Basic',
                'max_campaigns' => 5,
                'max_leads_per_campaign' => 100,
                'is_default' => true,
                'is_active' => true,
                'price' => 0.00,
            ]);
        }

        return $plan;
    }

    private function notifyAdminsRealtorRegistered(User $user): void
    {
        User::where('role', 'admin')->each(function (User $admin) use ($user) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'realtor_registered',
                'title' => 'New Realtor Registered',
                'message' => "{$user->name} ({$user->email}) registered with a social account.",
                'related_id' => $user->id,
                'related_type' => 'user',
            ]);
        });
    }
}
