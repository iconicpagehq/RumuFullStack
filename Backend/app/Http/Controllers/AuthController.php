<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use App\Services\MetaLeadApiService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Show register form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Handle register request (only for Realtors)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $basicPlan = $this->getOrCreateDefaultPlan();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'realtor', // Always create as Realtor
            'plan_id' => $basicPlan->id,
        ]);

        $this->notifyAdminsRealtorRegistered($user);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Redirect user to Google OAuth.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (ClientException $e) {
            return redirect()->route('login')->withErrors([
                'email' => [$this->googleOAuthErrorMessage($e)],
            ]);
        } catch (\Throwable $e) {
            Log::error('Google OAuth login failed', ['message' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([
                'email' => ['Google sign-in failed. Please try again or use email and password.'],
            ]);
        }

        $email = $googleUser->getEmail();
        if (!$email) {
            throw ValidationException::withMessages([
                'email' => ['Google account did not return an email address.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
            }
            if (!$user->google_avatar) {
                $user->google_avatar = $googleUser->getAvatar();
            }
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();
        } else {
            $basicPlan = $this->getOrCreateDefaultPlan();

            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'User'),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                // Set a random password so the account can still use "forgot password" later if needed.
                'password' => Hash::make(Str::random(32)),
                'role' => 'realtor',
                'plan_id' => $basicPlan->id,
            ]);

            $this->notifyAdminsRealtorRegistered($user);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Start linking Google account while logged in.
     */
    public function connectGoogle()
    {
        return Socialite::driver('google')
            ->redirectUrl(route('connect.google.callback'))
            ->stateless()
            ->redirect();
    }

    /**
     * Handle linking Google account while logged in.
     */
    public function connectGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('connect.google.callback'))
                ->stateless()
                ->user();
        } catch (ClientException $e) {
            return redirect()->route('realtor.profile')->with(
                'error',
                $this->googleOAuthErrorMessage($e)
            );
        } catch (\Throwable $e) {
            Log::error('Google OAuth connect failed', ['message' => $e->getMessage()]);

            return redirect()->route('realtor.profile')->with(
                'error',
                'Google account linking failed. Please try again.'
            );
        }

        $email = $googleUser->getEmail();
        if (!$email) {
            return redirect()->route('realtor.profile')
                ->with('error', 'Google account did not return an email address.');
        }

        $currentUser = Auth::user();

        // Prevent linking a Google account whose email belongs to another user.
        $emailOwner = User::where('email', $email)->first();
        if ($emailOwner && $emailOwner->id !== $currentUser->id) {
            return redirect()->route('realtor.profile')
                ->with('error', 'This Google account email is already used by another user.');
        }

        $currentUser->google_id = $googleUser->getId();
        $currentUser->google_avatar = $googleUser->getAvatar();

        if (!$currentUser->email_verified_at) {
            $currentUser->email_verified_at = now();
        }

        // If emails match, keep. If user's email is different but not used, update to Google email.
        if ($currentUser->email !== $email) {
            $currentUser->email = $email;
        }

        $currentUser->save();

        return redirect()->route('realtor.profile')
            ->with('success', 'Google account linked successfully!');
    }

    /**
     * Disconnect linked Google account.
     */
    public function disconnectGoogle()
    {
        $user = Auth::user();
        $user->google_id = null;
        $user->google_avatar = null;
        $user->save();

        return redirect()->route('realtor.profile')
            ->with('success', 'Google account unlinked successfully!');
    }

    /**
     * Redirect user to Facebook OAuth.
     */
    // public function redirectToFacebook()
    // {
    //     return Socialite::driver('facebook')
    //         ->scopes($this->facebookMetaScopes())
    //         ->stateless()
    //         ->redirect();
    // }

    // Atharva 
    public function redirectToFacebookLogin()
    {

        //  dd(config('services.facebook'));
        return Socialite::driver('facebook')
            ->scopes($this->facebookLoginScopes())
            ->stateless()
            ->redirect();
    }

    // Atharva handlecallback
    public function handleFacebookLoginCallback(Request $request)
    {
        try {
            $fbUser = Socialite::driver('facebook')
                ->scopes($this->facebookLoginScopes())
                ->stateless()
                ->user();
        } catch (\Throwable $e) {

            Log::error('Facebook Login Failed', [
                'message' => $e->getMessage()
            ]);

            return redirect()->route('login')->withErrors([
                'email' => ['Facebook sign-in failed. Please try again.'],
            ]);
        }

        $email = $fbUser->getEmail();

        if (!$email) {
            throw ValidationException::withMessages([
                'email' => [
                    'Facebook account did not return an email address.'
                ],
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user) {

            if (!$user->facebook_id) {
                $user->facebook_id = $fbUser->getId();
            }

            if (!$user->facebook_avatar) {
                $user->facebook_avatar = $fbUser->getAvatar();
            }

            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();
        } else {

            $basicPlan = $this->getOrCreateDefaultPlan();

            $user = User::create([
                'name' => $fbUser->getName()
                    ?: ($fbUser->getNickname() ?: 'User'),

                'email' => $email,

                'facebook_id' => $fbUser->getId(),

                'facebook_avatar' => $fbUser->getAvatar(),

                'email_verified_at' => now(),

                'password' => Hash::make(Str::random(32)),

                'role' => 'realtor',

                'plan_id' => $basicPlan->id,
            ]);
        }

        Auth::login($user, true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
    /**
     * Handle Facebook OAuth callback.
     */
    // public function handleFacebookCallback(Request $request, MetaLeadApiService $metaApi)
    // {
    //     // $fbUser = Socialite::driver('facebook')
    //     //     ->scopes($this->facebookMetaScopes())
    //     //     ->stateless()
    //     //     ->user();

    //     try {
    //         $fbUser = Socialite::driver('facebook')
    //             ->scopes($this->facebookMetaScopes())
    //             ->stateless()
    //             ->user();

    //         // dd($fbUser->getAvatar());
    //     } catch (\Throwable $e) {
    //         Log::error('Facebook OAuth login failed', ['message' => $e->getMessage()]);

    //         return redirect()->route('login')->withErrors([
    //             'email' => ['Facebook sign-in failed. Please try again.'],
    //         ]);
    //     }


    //     $email = $fbUser->getEmail();
    //     if (!$email) {
    //         throw ValidationException::withMessages([
    //             'email' => ['Facebook account did not return an email address. Please ensure email permission is granted.'],
    //         ]);
    //     }


    //     $user = User::where('email', $email)->first();

    //     if ($user) {
    //         if (!$user->facebook_id) {
    //             $user->facebook_id = $fbUser->getId();
    //         }
    //         if (!$user->facebook_avatar) {
    //             $user->facebook_avatar = $fbUser->getAvatar();
    //         }
    //         if (!$user->email_verified_at) {
    //             $user->email_verified_at = now();
    //         }
    //         $user->save();
    //     } else {
    //         $basicPlan = $this->getOrCreateDefaultPlan();

    //         $user = User::create([
    //             'name' => $fbUser->getName() ?: ($fbUser->getNickname() ?: 'User'),
    //             'email' => $email,
    //             'facebook_id' => $fbUser->getId(),
    //             'facebook_avatar' => $fbUser->getAvatar(),
    //             'email_verified_at' => now(),
    //             'password' => Hash::make(Str::random(32)),
    //             'role' => 'realtor',
    //             'plan_id' => $basicPlan->id,
    //         ]);

    //         $this->notifyAdminsRealtorRegistered($user);
    //     }

    //     $this->syncFacebookPageForMeta($user, $fbUser->token, $metaApi);

    //     Auth::login($user, true);
    //     $request->session()->regenerate();

    //     return redirect()->intended(route('dashboard'));
    // }

    /**
     * Start linking Facebook account while logged in.
     */
    public function connectFacebook(Request $request)
    {
        if ($request->filled('redirect')) {
            session(['facebook_connect_redirect' => $request->get('redirect')]);
        }

        $params = [
            'client_id'     => config('services.meta.app_id'),
            'redirect_uri'  => config('services.meta.redirect'),
            'response_type' => 'code',
            'auth_type'     => 'rerequest',
        ];

        // Facebook Login for Business requires config_id instead of scope array
        if (config('services.meta.config_id')) {
            $params['config_id'] = config('services.meta.config_id');
        } else {
            $params['scope'] = implode(',', $this->facebookMetaScopes());
        }

        return redirect(
            'https://www.facebook.com/' .
                config('services.meta.graph_version', 'v19.0') .
                '/dialog/oauth?' .
                http_build_query($params)
        );
    }

    /**
     * Handle linking Facebook account while logged in.
     */
    public function connectFacebookCallback(Request $request, MetaLeadApiService $metaApi)
    {
        $code = $request->query('code');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('realtor.profile')->with('error', 'Facebook connection was cancelled or failed.');
        }

        if (!$code) {
            return redirect()->route('realtor.profile')->with('error', 'Invalid Facebook callback.');
        }

        try {
            $tokenResponse = Http::get('https://graph.facebook.com/' . config('services.meta.graph_version', 'v19.0') . '/oauth/access_token', [
                'client_id' => config('services.meta.app_id'),
                'redirect_uri' => config('services.meta.redirect'),
                'client_secret' => config('services.meta.app_secret'),
                'code' => $code,
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to exchange code for access token.');
            }

            $userAccessToken = $tokenResponse->json('access_token');
            $currentUser = Auth::user();

            // Fetch basic profile to store facebook_id
            $meResponse = Http::get('https://graph.facebook.com/' . config('services.meta.graph_version', 'v19.0') . '/me', [
                'access_token' => $userAccessToken,
                'fields' => 'id,name',
            ]);
            
            if ($meResponse->successful()) {
                $meData = $meResponse->json();
                $currentUser->facebook_id = $meData['id'] ?? $currentUser->facebook_id;
                $currentUser->save();
            }

            $metaApi->syncUserPagesFromToken($currentUser, $userAccessToken);

            \App\Models\MetaSyncLog::create([
                'user_id' => $currentUser->id,
                'campaign_id' => null,
                'type' => 'success',
                'event_type' => 'connected',
                'message' => 'Facebook account linked and pages synced successfully.',
                'context' => ['facebook_id' => $currentUser->facebook_id],
            ]);

            $redirectTo = session()->pull('facebook_connect_redirect', route('realtor.profile'));

            return redirect($redirectTo)
                ->with('success', 'Facebook account linked and Pages synced successfully!');

        } catch (\Exception $e) {
            Log::error('Facebook OAuth Error', ['error' => $e->getMessage()]);
            return redirect()->route('realtor.profile')->with('error', 'Failed to connect Facebook account.');
        }
    }

    /**
     * Disconnect linked Facebook account.
     */
    public function disconnectFacebook()
    {
        $user = Auth::user();

        \App\Models\MetaSyncLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'type' => 'info',
            'event_type' => 'disconnected',
            'message' => 'Facebook account disconnected. Associated pages marked as disconnected.',
            'context' => ['facebook_id' => $user->facebook_id],
        ]);

        // Disconnect all associated meta pages, but keep the record
        $user->metaPages()->update(['status' => 'disconnected']);

        // For backward compatibility (to be removed in future phase)
        $user->facebook_page_id = null;
        $user->facebook_page_name = null;
        $user->facebook_page_access_token = null;
        $user->facebook_token_expired = false;
        
        // We still keep the App A facebook_id in case they use it for login
        $user->save();

        return redirect()->route('realtor.profile')
            ->with('success', 'Facebook account unlinked successfully!');
    }


    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect to appropriate dashboard based on role
     */
    public function dashboard()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('realtor.dashboard');
    }

    private function getOrCreateDefaultPlan(): \App\Models\Plan
    {
        $basicPlan = \App\Models\Plan::where('is_default', true)->where('is_active', true)->first()
            ?? \App\Models\Plan::active()->orderBy('id')->first();

        if (!$basicPlan) {
            $basicPlan = \App\Models\Plan::create([
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

    /**
     * @return array<int, string>
     */
    private function facebookMetaScopes(): array
    {
        return [
            'pages_show_list', 
            'pages_manage_metadata', 
            'leads_retrieval'
        ];
    }

    // atharva
    private function facebookLoginScopes(): array
    {
        return [
            'email',
        ];
    }

    private function syncFacebookPageForMeta(User $user, ?string $token, MetaLeadApiService $metaApi): void
    {
        if (!$token) {
            return;
        }

        $metaApi->syncUserPageFromToken($user, $token);
    }

    private function googleOAuthErrorMessage(ClientException $e): string
    {
        $body = (string) $e->getResponse()?->getBody();
        $payload = json_decode($body, true);
        $error = is_array($payload) ? ($payload['error'] ?? null) : null;

        return match ($error) {
            'deleted_client', 'invalid_client' => 'Google OAuth is misconfigured: the client ID/secret in .env is invalid or was deleted in Google Cloud Console. Create a new OAuth client and update GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.',
            'redirect_uri_mismatch' => 'Google OAuth redirect URI mismatch. Add both callback URLs from the README to your Google Cloud OAuth client.',
            'access_denied' => 'Google sign-in was cancelled or denied.',
            default => 'Google sign-in failed. Check GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and redirect URIs in Google Cloud Console.',
        };
    }
}
