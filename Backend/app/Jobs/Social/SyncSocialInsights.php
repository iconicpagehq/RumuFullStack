<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialPage;
use App\Services\Social\Meta\MetaInsightsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSocialInsights implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $socialPageId;

    public function __construct(int $socialPageId)
    {
        $this->socialPageId = $socialPageId;
    }

    public function handle(MetaInsightsService $insightsService): void
    {
        $page = SocialPage::find($this->socialPageId);
        if (!$page || !$page->is_active) {
            return;
        }

        try {
            if ($page->platform === 'facebook') {
                $insightsService->syncFacebookInsights($page);
            } elseif ($page->platform === 'instagram') {
                $insightsService->syncInstagramInsights($page);
            }
        } catch (Throwable $e) {
            Log::warning("Could not sync insights for page #{$this->socialPageId}: {$e->getMessage()}");
        }
    }
}

