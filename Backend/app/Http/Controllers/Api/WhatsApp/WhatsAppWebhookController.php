<?php

namespace App\Http\Controllers\Api\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppWebhookService $webhookService;

    public function __construct(WhatsAppWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Meta Webhook Verification (GET).
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->webhookService->verifyWebhook($request);

        if ($challenge !== null) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed: Invalid verify token or mode.', [
            'query' => $request->query(),
        ]);

        return response('Forbidden: Verification failed', 403);
    }

    /**
     * Meta Webhook Ingestion & Event Handling (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256');
        $rawContent = $request->getContent();

        // Validate Meta signature using app secret
        if (!$this->webhookService->validateSignature($rawContent, $signature)) {
            Log::warning('WhatsApp webhook rejected: Invalid X-Hub-Signature-256 header.');
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 403);
        }

        $payload = $request->json()->all();

        // Ingest and enqueue for background processing
        $result = $this->webhookService->ingestWebhook($payload, $rawContent);

        // Always acknowledge Meta with 200 within 3 seconds
        return response()->json([
            'status' => 'EVENT_RECEIVED',
            'detail' => $result,
        ], 200);
    }
}
