<?php

namespace App\Http\Controllers\Api\Social;

use App\Models\Social\SocialComment;
use App\Models\Social\SocialPost;
use App\Services\Social\Meta\MetaCommentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class SocialCommentController extends BaseSocialController
{
    protected MetaCommentService $commentService;

    public function __construct(MetaCommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Get comments for a post.
     * GET /api/v1/social/posts/{id}/comments
     */
    public function index(Request $request, int $postId)
    {
        $post = $this->scopeTenant(SocialPost::query(), $request)->with('page')->findOrFail($postId);

        // On-demand fetch from Meta if comments are empty in DB
        $existingCount = SocialComment::where('social_post_id', $post->id)->count();
        if ($existingCount === 0 && $post->page && $post->page->is_active && $post->page->access_token) {
            try {
                $postService = app(\App\Services\Social\Meta\MetaPostService::class);
                $response = $this->commentService->getComments(
                    $post->external_id,
                    $post->page->access_token,
                    50,
                    null,
                    $post->page->platform
                );
                $metaComments = $response['data'] ?? [];

                foreach ($metaComments as $commentData) {
                    $comment = $postService->syncComment($post, $commentData);

                    if (isset($commentData['comments']['data']) && is_array($commentData['comments']['data'])) {
                        foreach ($commentData['comments']['data'] as $replyData) {
                            $postService->syncComment($post, $replyData, $comment->id);
                        }
                    }
                }
            } catch (Throwable $e) {
                // Ignore gracefully and serve existing DB records
            }
        }

        $comments = SocialComment::where('social_post_id', $post->id)
            ->whereNull('parent_comment_id')
            ->with(['replies' => function ($q) {
                $q->orderBy('created_time', 'asc');
            }])
            ->orderBy('created_time', 'desc')
            ->paginate($request->query('per_page', 25));

        return $this->paginated($comments, 'Comments retrieved successfully.');
    }

    /**
     * Sync comments for a post directly from Meta Graph API.
     * POST /api/v1/social/posts/{id}/sync-comments
     */
    public function sync(Request $request, int $postId, \App\Services\Social\Meta\MetaPostService $postService)
    {
        $post = $this->scopeTenant(SocialPost::query(), $request)->with('page')->findOrFail($postId);
        $page = $post->page;

        if (!$page || !$page->is_active) {
            return $this->error('The associated social page is inactive or disconnected.', 400);
        }

        try {
            $response = $this->commentService->getComments(
                $post->external_id,
                $page->access_token,
                50,
                null,
                $page->platform
            );
            $comments = $response['data'] ?? [];
            $syncedCount = 0;

            foreach ($comments as $commentData) {
                $comment = $postService->syncComment($post, $commentData);
                $syncedCount++;

                if (isset($commentData['comments']['data']) && is_array($commentData['comments']['data'])) {
                    foreach ($commentData['comments']['data'] as $replyData) {
                        $postService->syncComment($post, $replyData, $comment->id);
                        $syncedCount++;
                    }
                } elseif (isset($commentData['replies']['data']) && is_array($commentData['replies']['data'])) {
                    foreach ($commentData['replies']['data'] as $replyData) {
                        $postService->syncComment($post, $replyData, $comment->id);
                        $syncedCount++;
                    }
                }
            }

            return $this->success(['synced_count' => $syncedCount], "Successfully synced {$syncedCount} comments from {$page->platform}.");
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'pages_read_user_content')) {
                return $this->error(
                    "Facebook comments require the 'pages_read_user_content' permission approved via Meta App Review. Instagram post comments sync automatically via 'instagram_manage_comments'.",
                    403
                );
            }
            if (str_contains($msg, 'OAuthException') || str_contains($msg, 'permission')) {
                return $this->error("Meta permission missing: {$msg}. Please check your Meta app permissions or reconnect your account.", 403);
            }
            return $this->error("Failed to sync comments: {$msg}", 500);
        }
    }

    /**
     * Reply to a comment.
     * POST /api/v1/social/comments/{id}/reply
     */
    public function reply(Request $request, int $commentId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $comment = $this->scopeTenant(SocialComment::query(), $request)
            ->with(['post.page'])
            ->findOrFail($commentId);

        $page = $comment->post->page;
        if (!$page || !$page->is_active) {
            return $this->error('The associated social page is inactive or disconnected.', 400);
        }

        try {
            // Post reply to Meta Graph API
            $metaResponse = $this->commentService->replyToComment(
                $comment->external_id,
                $request->input('message'),
                $page->access_token
            );

            $replyExternalId = $metaResponse['id'] ?? null;

            // Persist reply to database
            $reply = SocialComment::create([
                'company_id' => $comment->company_id,
                'user_id' => $comment->user_id,
                'social_post_id' => $comment->social_post_id,
                'parent_comment_id' => $comment->id,
                'platform' => $comment->platform,
                'external_id' => $replyExternalId ?: ('reply_' . uniqid()),
                'from_id' => $page->external_id,
                'from_name' => $page->name,
                'message' => $request->input('message'),
                'created_time' => Carbon::now(),
                'raw_data' => $metaResponse,
            ]);

            return $this->success($reply, 'Reply posted successfully.');
        } catch (Throwable $e) {
            return $this->error("Failed to post comment reply: {$e->getMessage()}", 500);
        }
    }

    /**
     * Moderate a comment (Hide / Unhide).
     * PATCH /api/v1/social/comments/{id}
     */
    public function update(Request $request, int $commentId)
    {
        $request->validate([
            'is_hidden' => 'required|boolean',
        ]);

        $comment = $this->scopeTenant(SocialComment::query(), $request)
            ->with(['post.page'])
            ->findOrFail($commentId);

        $page = $comment->post->page;
        if (!$page || !$page->is_active) {
            return $this->error('The associated social page is inactive or disconnected.', 400);
        }

        try {
            $hide = (bool) $request->input('is_hidden');
            $this->commentService->setCommentHidden($comment->external_id, $hide, $page->access_token);

            $comment->update(['is_hidden' => $hide]);

            return $this->success($comment, 'Comment moderation updated.');
        } catch (Throwable $e) {
            return $this->error("Failed to moderate comment: {$e->getMessage()}", 500);
        }
    }

    /**
     * Delete a comment.
     * DELETE /api/v1/social/comments/{id}
     */
    public function destroy(Request $request, int $commentId)
    {
        $comment = $this->scopeTenant(SocialComment::query(), $request)
            ->with(['post.page'])
            ->findOrFail($commentId);

        $page = $comment->post->page;
        if ($page && $page->is_active) {
            try {
                $this->commentService->deleteComment($comment->external_id, $page->access_token);
            } catch (Throwable $e) {
                // Log and continue soft marking
            }
        }

        $comment->update(['is_deleted' => true]);

        return $this->success(null, 'Comment deleted successfully.');
    }
}

