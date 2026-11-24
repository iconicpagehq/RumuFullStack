<?php

namespace App\Services\Social\Meta;

class InstagramService
{
    protected MetaApiClient $client;

    public function __construct(MetaApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve Instagram Business or Creator account details.
     */
    public function getAccountInfo(string $igBusinessId, string $accessToken): array
    {
        return $this->client->get("/{$igBusinessId}", [
            'fields' => 'id,username,name,profile_picture_url,biography,followers_count,follows_count,media_count',
        ], $accessToken);
    }

    /**
     * Retrieve Instagram media (images, videos, carousels, reels).
     */
    public function getMedia(string $igBusinessId, string $accessToken, int $limit = 25, ?string $after = null): array
    {
        $query = [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
            'limit' => $limit,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        return $this->client->get("/{$igBusinessId}/media", $query, $accessToken);
    }

    /**
     * Retrieve specific Instagram media item.
     */
    public function getMediaDetails(string $mediaId, string $accessToken): array
    {
        return $this->client->get("/{$mediaId}", [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
        ], $accessToken);
    }

    /**
     * Retrieve Instagram account-level insights.
     */
    public function getAccountInsights(
        string $igBusinessId,
        string $accessToken,
        array $metrics = ['impressions', 'reach'],
        string $period = 'day'
    ): array {
        return $this->client->get("/{$igBusinessId}/insights", [
            'metric' => implode(',', $metrics),
            'period' => $period,
        ], $accessToken);
    }

    /**
     * Retrieve media-level insights.
     */
    public function getMediaInsights(string $mediaId, string $accessToken, string $mediaType = 'IMAGE'): array
    {
        // Select metric set based on media type
        $metrics = ($mediaType === 'VIDEO')
            ? ['engagement', 'impressions', 'reach', 'saved', 'video_views']
            : ['engagement', 'impressions', 'reach', 'saved'];

        return $this->client->get("/{$mediaId}/insights", [
            'metric' => implode(',', $metrics),
        ], $accessToken);
    }

    /**
     * Retrieve tagged media where this Instagram account was tagged.
     */
    public function getTaggedMedia(string $igBusinessId, string $accessToken, int $limit = 25): array
    {
        return $this->client->get("/{$igBusinessId}/tags", [
            'fields' => 'id,caption,media_type,media_url,permalink,timestamp,username',
            'limit' => $limit,
        ], $accessToken);
    }
}

