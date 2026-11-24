<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialAccount;
use App\Services\Social\Meta\MetaTokenService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshMetaToken implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $socialAccountId;

    public function __construct(int $socialAccountId)
    {
        $this->socialAccountId = $socialAccountId;
    }

    public function handle(MetaTokenService $tokenService): void
    {
        $account = SocialAccount::find($this->socialAccountId);
        if (!$account || $account->status === 'disconnected') {
            return;
        }

        try {
            $info = $tokenService->inspectToken($account->access_token);
            $isValid = $info['is_valid'] ?? false;

            if (!$isValid) {
                $account->update(['status' => 'expired']);
                return;
            }

            if (isset($info['data_access_expires_at'])) {
                $expiresAt = Carbon::createFromTimestamp($info['data_access_expires_at']);
                $account->update(['token_expires_at' => $expiresAt]);
            }
        } catch (Throwable $e) {
            Log::warning("Token inspection failed for social account #{$this->socialAccountId}: {$e->getMessage()}");
        }
    }
}

