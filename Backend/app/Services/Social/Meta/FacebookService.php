<?php

namespace App\Services\Social\Meta;

class FacebookService
{
    protected MetaApiClient $client;

    public function __construct(MetaApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve authenticated user profile information.
     */
    public function getUserProfile(string $userAccessToken): array
    {
        return $this->client->get('/me', [
            'fields' => 'id,name,email,picture.width(200).height(200)',
        ], $userAccessToken);
    }

    /**
     * Retrieve all Facebook Pages managed by the user, including connected Instagram accounts.
     * Checks both /me/accounts and any business asset target IDs in the token.
     */
    public function getUserPages(string $userAccessToken, array $targetIds = []): array
    {
        $pages = [];
        $seen = [];

        try {
            $response = $this->client->get('/me/accounts', [
                'fields' => 'id,name,username,category,access_token,tasks,instagram_business_account{id,username,name,profile_picture_url}',
                'limit' => 100,
            ], $userAccessToken);

            foreach ($response['data'] ?? [] as $page) {
                $pid = (string) $page['id'];
                $pages[] = $page;
                $seen[$pid] = true;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Could not fetch /me/accounts: {$e->getMessage()}");
        }

        // Also check any target IDs from granular_scopes, config/env, and known pages in our database
        $knownPageIds = \App\Models\Social\SocialPage::where('platform', 'facebook')
            ->pluck('external_id')
            ->toArray();

        $configPages = config('services.meta.additional_page_ids', '111958791970913');
        $envPageIds = array_filter(array_map('trim', explode(',', (string) $configPages)));
        $defaultBusinessPages = ['111958791970913'];

        $allTargetIds = array_unique(array_filter(array_merge(
            $targetIds,
            $knownPageIds,
            $envPageIds,
            $defaultBusinessPages
        )));

        foreach ($allTargetIds as $tid) {
            $tidStr = (string) $tid;
            if (isset($seen[$tidStr])) {
                continue;
            }
            try {
                $pageData = $this->client->get("/{$tidStr}", [
                    'fields' => 'id,name,username,category,access_token,instagram_business_account{id,username,name,profile_picture_url}',
                ], $userAccessToken);

                if (!empty($pageData['id'])) {
                    if (empty($pageData['access_token'])) {
                        $pageData['access_token'] = $userAccessToken;
                    }
                    $pages[] = $pageData;
                    $seen[$tidStr] = true;
                }
            } catch (\Throwable $te) {
                \Illuminate\Support\Facades\Log::info("Could not fetch direct page #{$tidStr}: {$te->getMessage()}");
            }
        }

        return $pages;
    }

    /**
     * Retrieve feed posts for a Facebook Page.
     */
    public function getPagePosts(string $pageExternalId, string $pageAccessToken, int $limit = 25, ?string $after = null): array
    {
        $fields = 'id,message,story,permalink_url,created_time,full_picture,shares';

        $query = [
            'fields' => $fields,
            'limit' => $limit,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        try {
            return $this->client->get("/{$pageExternalId}/published_posts", $query, $pageAccessToken);
        } catch (\Throwable $e) {
            return $this->client->get("/{$pageExternalId}/feed", $query, $pageAccessToken);
        }
    }

    /**
     * Retrieve specific Page post details.
     */
    public function getPagePost(string $postId, string $pageAccessToken): array
    {
        return $this->client->get("/{$postId}", [
            'fields' => 'id,message,permalink_url,created_time,full_picture,shares,reactions.summary(total_count),comments.summary(total_count)',
        ], $pageAccessToken);
    }

    /**
     * Retrieve Page level insights.
     */
    public function getPageInsights(
        string $pageExternalId,
        string $pageAccessToken,
        array $metrics = ['page_impressions', 'page_post_engagements'],
        string $period = 'day'
    ): array {
        return $this->client->get("/{$pageExternalId}/insights", [
            'metric' => implode(',', $metrics),
            'period' => $period,
        ], $pageAccessToken);
    }

    /**
     * Retrieve posts tagging/mentioning the Facebook Page.
     */
    public function getPageMentions(string $pageExternalId, string $pageAccessToken, int $limit = 25): array
    {
        return $this->client->get("/{$pageExternalId}/tagged", [
            'fields' => 'id,message,created_time,from,permalink_url,full_picture',
            'limit' => $limit,
        ], $pageAccessToken);
    }

    /**
     * Subscribe Facebook Page to Webhooks for real-time updates.
     */
    public function subscribePageWebhooks(string $pageExternalId, string $pageAccessToken): array
    {
        return $this->client->post("/{$pageExternalId}/subscribed_apps", [
            'subscribed_fields' => 'feed,mention,messages,messaging_postbacks,message_reads,message_deliveries',
        ], $pageAccessToken);
    }
}

