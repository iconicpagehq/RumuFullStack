<?php

namespace App\Services\Social\Meta;

use App\Models\Social\SocialPage;
use App\Models\Social\SocialPost;
use App\Models\Social\SocialComment;
use Carbon\Carbon;

class MetaPostService
{
    /**
     * Store or update a normalized Facebook post.
     */
    public function syncFacebookPost(SocialPage $page, array $data): SocialPost
    {
        $externalId = $data['id'];
        $publishedAt = isset($data['created_time']) ? Carbon::parse($data['created_time']) : Carbon::now();

        $likesCount = $data['reactions']['summary']['total_count'] ?? 0;
        $commentsCount = $data['comments']['summary']['total_count'] ?? 0;
        $sharesCount = $data['shares']['count'] ?? 0;
        $message = $data['message'] ?? $data['story'] ?? null;

        return SocialPost::updateOrCreate(
            [
                'social_page_id' => $page->id,
                'external_id' => $externalId,
            ],
            [
                'company_id' => $page->company_id,
                'user_id' => $page->user_id,
                'platform' => 'facebook',
                'permalink' => $data['permalink_url'] ?? null,
                'message' => $message,
                'media_type' => isset($data['full_picture']) ? 'image' : 'status',
                'media_url' => $data['full_picture'] ?? null,
                'thumbnail_url' => $data['full_picture'] ?? null,
                'likes_count' => $likesCount,
                'comments_count' => $commentsCount,
                'shares_count' => $sharesCount,
                'published_at' => $publishedAt,
                'raw_data' => $data,
            ]
        );
    }

    /**
     * Store or update a normalized Instagram media post.
     */
    public function syncInstagramMedia(SocialPage $page, array $data): SocialPost
    {
        $externalId = $data['id'];
        $publishedAt = isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : Carbon::now();

        return SocialPost::updateOrCreate(
            [
                'social_page_id' => $page->id,
                'external_id' => $externalId,
            ],
            [
                'company_id' => $page->company_id,
                'user_id' => $page->user_id,
                'platform' => 'instagram',
                'permalink' => $data['permalink'] ?? null,
                'message' => $data['caption'] ?? null,
                'media_type' => strtolower($data['media_type'] ?? 'image'),
                'media_url' => $data['media_url'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? $data['media_url'] ?? null,
                'likes_count' => $data['like_count'] ?? 0,
                'comments_count' => $data['comments_count'] ?? 0,
                'shares_count' => 0,
                'published_at' => $publishedAt,
                'raw_data' => $data,
            ]
        );
    }

    /**
     * Store or update a post comment.
     */
    public function syncComment(SocialPost $post, array $data, ?int $parentCommentId = null): SocialComment
    {
        $externalId = $data['id'];
        $createdTime = isset($data['created_time']) 
            ? Carbon::parse($data['created_time']) 
            : (isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : Carbon::now());

        $fromId = $data['from']['id'] ?? $data['user']['id'] ?? $data['id'] ?? null;
        $fromName = $data['from']['name'] ?? $data['from']['username'] ?? $data['username'] ?? $data['user']['username'] ?? 'User';
        $message = $data['message'] ?? $data['text'] ?? '';

        return SocialComment::updateOrCreate(
            [
                'social_post_id' => $post->id,
                'external_id' => $externalId,
            ],
            [
                'company_id' => $post->company_id,
                'user_id' => $post->user_id,
                'parent_comment_id' => $parentCommentId,
                'platform' => $post->platform,
                'from_id' => $fromId,
                'from_name' => $fromName,
                'message' => $message,
                'created_time' => $createdTime,
                'raw_data' => $data,
            ]
        );
    }
}

