<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialPost;
use App\Services\Social\Meta\MetaCommentService;
use App\Services\Social\Meta\MetaPostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSocialComments implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $socialPostId;

    public function __construct(int $socialPostId)
    {
        $this->socialPostId = $socialPostId;
    }

    public function handle(MetaCommentService $commentService, MetaPostService $postService): void
    {
        $post = SocialPost::with('page')->find($this->socialPostId);
        if (!$post || !$post->page || !$post->page->is_active) {
            return;
        }

        try {
            $response = $commentService->getComments(
                $post->external_id,
                $post->page->access_token,
                50,
                null,
                $post->page->platform
            );
            $comments = $response['data'] ?? [];

            foreach ($comments as $commentData) {
                $comment = $postService->syncComment($post, $commentData);

                // Check nested replies (Facebook 'comments' or Instagram 'replies')
                $replies = $commentData['comments']['data'] ?? $commentData['replies']['data'] ?? [];
                if (is_array($replies)) {
                    foreach ($replies as $replyData) {
                        $postService->syncComment($post, $replyData, $comment->id);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning("Could not sync comments for post #{$this->socialPostId}: {$e->getMessage()}");
        }
    }
}

