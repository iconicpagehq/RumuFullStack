<?php

namespace App\Http\Controllers\Api\Social;

use App\Http\Controllers\Controller;
use App\Services\Social\Meta\MetaWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class SocialWebhookController extends Controller
{
    protected MetaWebhookService $webhookService;

    public function __construct(MetaWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle Meta Webhook verification challenge handshake.
     * GET /api/v1/social/meta/webhook
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?? '';
        $token = $request->query('hub_verify_token') ?? '';
        $challenge = $request->query('hub_challenge') ?? '';

        try {
            $verifiedChallenge = $this->webhookService->verifyWebhook($mode, $token, $challenge);
            return response($verifiedChallenge, 200)->header('Content-Type', 'text/plain');
        } catch (Throwable $e) {
            Log::warning("Meta webhook verification rejected: {$e->getMessage()}");
            return response('Forbidden', 403);
        }
    }

    /**
     * Receive incoming real-time events from Meta.
     * POST /api/v1/social/meta/webhook
     */
    public function handle(Request $request): Response
    {
        $signature = $request->header('X-Hub-Signature-256');
        $rawContent = $request->getContent();

        // 1. Signature validation
        if (!$this->webhookService->validateSignature($rawContent, $signature)) {
            Log::warning('Meta webhook signature validation failed.', [
                'has_signature' => !empty($signature),
            ]);
            return response('Invalid signature', 403);
        }

        // 2. Ingest payload and dispatch background jobs
        try {
            $payload = $request->all();
            $this->webhookService->ingestPayload($payload);
        } catch (Throwable $e) {
            Log::error("Failed to ingest Meta webhook: {$e->getMessage()}");
        }

        // Must acknowledge within 20 seconds to prevent retries
        return response('EVENT_RECEIVED', 200);
    }
}

