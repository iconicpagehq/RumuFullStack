<?php

namespace App\Services\Social\Meta;

use App\Models\Social\SocialMention;
use App\Models\Social\SocialPage;
use Carbon\Carbon;

class MetaMentionService
{
    /**
     * Store or update Facebook tagged mention.
     */
    public function syncFacebookMention(SocialPage $page, array $data): SocialMention
    {
        $externalId = $data['id'];
        $mentionedAt = isset($data['created_time']) ? Carbon::parse($data['created_time']) : Carbon::now();

        return SocialMention::updateOrCreate(
            [
                'social_page_id' => $page->id,
                'external_id' => $externalId,
            ],
            [
                'company_id' => $page->company_id,
                'user_id' => $page->user_id,
                'platform' => 'facebook',
                'media_id' => $externalId,
                'mention_type' => 'post',
                'media_url' => $data['full_picture'] ?? null,
                'caption' => $data['message'] ?? null,
                'author_username' => $data['from']['name'] ?? null,
                'mentioned_at' => $mentionedAt,
                'raw_data' => $data,
            ]
        );
    }

    /**
     * Store or update Instagram tagged media mention.
     */
    public function syncInstagramMention(SocialPage $page, array $data): SocialMention
    {
        $externalId = $data['id'];
        $mentionedAt = isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : Carbon::now();

        return SocialMention::updateOrCreate(
            [
                'social_page_id' => $page->id,
                'external_id' => $externalId,
            ],
            [
                'company_id' => $page->company_id,
                'user_id' => $page->user_id,
                'platform' => 'instagram',
                'media_id' => $externalId,
                'mention_type' => strtolower($data['media_type'] ?? 'post'),
                'media_url' => $data['media_url'] ?? null,
                'caption' => $data['caption'] ?? null,
                'author_username' => $data['username'] ?? null,
                'mentioned_at' => $mentionedAt,
                'raw_data' => $data,
            ]
        );
    }
}

