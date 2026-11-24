<?php

namespace App\Http\Controllers\Api\Social;

use App\Models\Social\SocialAccount;
use App\Models\Social\SocialComment;
use App\Models\Social\SocialConversation;
use App\Models\Social\SocialInsight;
use App\Models\Social\SocialMention;
use App\Models\Social\SocialPage;
use App\Models\Social\SocialPost;
use App\Services\Social\Meta\InstagramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class SocialAnalyticsController extends BaseSocialController
{
    protected InstagramService $instagramService;

    public function __construct(InstagramService $instagramService)
    {
        $this->instagramService = $instagramService;
    }

    /**
     * Overview metrics for social dashboard.
     * GET /api/v1/social/analytics/overview
     */
    public function overview(Request $request)
    {
        // 1. Account counts
        $connectedFbPages = $this->scopeTenant(SocialPage::query(), $request)
            ->where('platform', 'facebook')
            ->where('is_active', true)
            ->count();

        $connectedIgAccounts = $this->scopeTenant(SocialPage::query(), $request)
            ->where('platform', 'instagram')
            ->where('is_active', true)
            ->count();

        // 2. Message counts
        $totalConversations = $this->scopeTenant(SocialConversation::query(), $request)->count();
        $unreadMessages = $this->scopeTenant(SocialConversation::query(), $request)->sum('unread_count');

        // 3. Comments and Mentions count
        $totalComments = $this->scopeTenant(SocialComment::query(), $request)->count();
        $totalMentions = $this->scopeTenant(SocialMention::query(), $request)->count();

        // 4. Post performance aggregation
        $postsQuery = $this->scopeTenant(SocialPost::query(), $request);
        $totalPosts = $postsQuery->count();
        $totalLikes = (clone $postsQuery)->sum('likes_count');
        $totalPostComments = (clone $postsQuery)->sum('comments_count');
        $totalShares = (clone $postsQuery)->sum('shares_count');

        // 5. Recent daily insights (last 14 days)
        $startDate = Carbon::today()->subDays(14)->toDateString();
        $dailyInsights = $this->scopeTenant(SocialInsight::query(), $request)
            ->where('recorded_date', '>=', $startDate)
            ->orderBy('recorded_date', 'asc')
            ->get();

        return $this->success([
            'accounts' => [
                'facebook_pages' => $connectedFbPages,
                'instagram_accounts' => $connectedIgAccounts,
            ],
            'messages' => [
                'total_conversations' => $totalConversations,
                'unread' => (int) $unreadMessages,
            ],
            'engagement' => [
                'total_posts' => $totalPosts,
                'total_comments' => $totalComments,
                'total_mentions' => $totalMentions,
                'total_likes' => (int) $totalLikes,
                'total_shares' => (int) $totalShares,
            ],
            'insights' => $dailyInsights,
        ], 'Social analytics overview retrieved successfully.');
    }

    /**
     * Get analytics for a specific post.
     * GET /api/v1/social/analytics/posts/{id}
     */
    public function postAnalytics(Request $request, int $id)
    {
        $post = $this->scopeTenant(SocialPost::query(), $request)
            ->with('page')
            ->findOrFail($id);

        $mediaInsights = [];

        // If it's an Instagram post, attempt fetching live media insights
        if ($post->platform === 'instagram' && $post->page && $post->page->is_active) {
            try {
                $resp = $this->instagramService->getMediaInsights(
                    $post->external_id,
                    $post->page->access_token,
                    $post->media_type ?: 'IMAGE'
                );
                $mediaInsights = $resp['data'] ?? [];
            } catch (Throwable $e) {
                // If media is not eligible for insights (e.g. older than 24h for stories or personal post), fallback to stored stats
            }
        }

        return $this->success([
            'post' => $post,
            'media_insights' => $mediaInsights,
        ], 'Post analytics retrieved successfully.');
    }
}

