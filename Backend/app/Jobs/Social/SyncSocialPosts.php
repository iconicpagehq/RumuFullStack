<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialPage;
use App\Models\Social\SocialSyncLog;
use App\Services\Social\Meta\FacebookService;
use App\Services\Social\Meta\InstagramService;
use App\Services\Social\Meta\MetaPostService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSocialPosts implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $socialPageId;

    public function __construct(int $socialPageId)
    {
        $this->socialPageId = $socialPageId;
    }

    public function handle(
        FacebookService $facebookService,
        InstagramService $instagramService,
        MetaPostService $postService
    ): void {
        $page = SocialPage::find($this->socialPageId);
        if (!$page || !$page->is_active) {
            return;
        }

        $log = SocialSyncLog::create([
            'company_id' => $page->company_id,
            'user_id' => $page->user_id,
            'social_page_id' => $page->id,
            'platform' => $page->platform,
            'sync_type' => 'posts',
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
        ]);

        try {
            $count = 0;

            if ($page->platform === 'facebook') {
                $response = $facebookService->getPagePosts($page->external_id, $page->access_token, 50);
                $posts = $response['data'] ?? [];

                foreach ($posts as $postData) {
                    $post = $postService->syncFacebookPost($page, $postData);
                    $count++;

                    // Also dispatch comments sync for recent post
                    SyncSocialComments::dispatch($post->id);
                }
            } elseif ($page->platform === 'instagram') {
                $response = $instagramService->getMedia($page->external_id, $page->access_token, 50);
                $mediaItems = $response['data'] ?? [];

                foreach ($mediaItems as $mediaData) {
                    $post = $postService->syncInstagramMedia($page, $mediaData);
                    $count++;

                    // Also dispatch comments sync
                    SyncSocialComments::dispatch($post->id);
                }
            }

            $page->update(['last_synced_at' => Carbon::now()]);

            $log->update([
                'status' => 'completed',
                'items_synced' => $count,
                'completed_at' => Carbon::now(),
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to sync social posts for page #{$this->socialPageId}", [
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => Carbon::now(),
            ]);

            // Do not rethrow in sync queue to allow connection to proceed cleanly
            Log::warning("Social post sync deferred for page #{$this->socialPageId}: {$e->getMessage()}");
        }
    }
}

