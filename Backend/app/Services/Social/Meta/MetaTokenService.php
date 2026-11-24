<?php

namespace App\Services\Social\Meta;

use Carbon\Carbon;
use Exception;

class MetaTokenService
{
    protected MetaApiClient $client;
    protected string $appId;
    protected string $appSecret;

    public function __construct(MetaApiClient $client)
    {
        $this->client = $client;
        $this->appId = (string) config('services.meta.app_id');
        $this->appSecret = (string) config('services.meta.app_secret');
    }

    /**
     * Exchange short-lived token for long-lived user token (~60 days).
     */
    public function exchangeForLongLivedUserToken(string $shortLivedToken): array
    {
        $response = $this->client->get('/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        $token = $response['access_token'] ?? null;
        $expiresIn = $response['expires_in'] ?? null;

        if (!$token) {
            throw new Exception('Failed to obtain long-lived Meta user token.');
        }

        $expiresAt = $expiresIn ? Carbon::now()->addSeconds($expiresIn) : Carbon::now()->addDays(60);

        return [
            'access_token' => $token,
            'token_type' => $response['token_type'] ?? 'bearer',
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Inspect token details and permissions via /debug_token.
     */
    public function inspectToken(string $inputToken): array
    {
        $appAccessToken = "{$this->appId}|{$this->appSecret}";

        $response = $this->client->get('/debug_token', [
            'input_token' => $inputToken,
            'access_token' => $appAccessToken,
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Get permanent page access token for an authorized Facebook Page.
     */
    public function getPageAccessToken(string $pageExternalId, string $userAccessToken): ?string
    {
        $response = $this->client->get("/{$pageExternalId}", [
            'fields' => 'access_token',
        ], $userAccessToken);

        return $response['access_token'] ?? null;
    }
}

