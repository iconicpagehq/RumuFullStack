<?php

namespace App\Services\Social\Meta;

use App\Models\Social\SocialOAuthState;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class MetaOAuthService
{
    protected MetaApiClient $client;
    protected MetaTokenService $tokenService;
    protected string $appId;
    protected string $appSecret;
    protected string $defaultRedirectUri;

    public const REQUIRED_SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_read_user_content',
        'pages_manage_posts',
        'pages_manage_engagement',
        'pages_messaging',
        'instagram_basic',
        'instagram_manage_comments',
        'instagram_manage_messages',
        'instagram_manage_insights',
        'read_insights',
        'business_management',
        'whatsapp_business_management',
        'whatsapp_business_messaging',
    ];

    public function __construct(MetaApiClient $client, MetaTokenService $tokenService)
    {
        $this->client = $client;
        $this->tokenService = $tokenService;
        $this->appId = (string) config('services.meta.app_id');
        $this->appSecret = (string) config('services.meta.app_secret');
        $this->defaultRedirectUri = (string) (config('services.meta.redirect') ?: env('META_REDIRECT_URI', ''));
    }

    /**
     * Generate authorization URL with secure single-use state.
     */
    public function generateAuthorizationUrl(int $userId, ?int $companyId, ?string $redirectUri = null): string
    {
        $resolvedRedirect = $redirectUri ?: $this->defaultRedirectUri;
        $state = Str::random(40);

        SocialOAuthState::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'state' => $state,
            'redirect_uri' => $resolvedRedirect,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $resolvedRedirect,
            'state' => $state,
            'response_type' => 'code',
            'auth_type' => 'rerequest',
        ];

        $configId = config('services.meta.config_id');
        if (!empty($configId)) {
            $params['config_id'] = $configId;
        } else {
            $params['scope'] = implode(',', self::REQUIRED_SCOPES);
        }

        $query = http_build_query($params);

        $version = $this->client->getVersion();
        return "https://www.facebook.com/{$version}/dialog/oauth?{$query}";
    }

    /**
     * Validate incoming state from OAuth callback.
     */
    public function validateState(string $state): SocialOAuthState
    {
        $record = SocialOAuthState::where('state', $state)->first();

        if (!$record) {
            throw new Exception('Invalid OAuth state. Please initiate authorization again.', 400);
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            $record->delete();
            throw new Exception('OAuth authorization state expired. Please try again.', 400);
        }

        // Single use state
        $cloned = clone $record;
        $record->delete();

        return $cloned;
    }

    /**
     * Exchange authorization code for user access token.
     */
    public function exchangeCodeForToken(string $code, ?string $redirectUri = null): array
    {
        $resolvedRedirect = $redirectUri ?: $this->defaultRedirectUri;

        $response = $this->client->get('/oauth/access_token', [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $resolvedRedirect,
            'code' => $code,
        ]);

        $shortLivedToken = $response['access_token'] ?? null;
        if (!$shortLivedToken) {
            throw new Exception('Failed to retrieve access token from Meta OAuth code.');
        }

        // Exchange for long-lived access token
        return $this->tokenService->exchangeForLongLivedUserToken($shortLivedToken);
    }
}

