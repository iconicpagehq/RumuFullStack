<?php

namespace App\Services\WhatsApp;

use App\Jobs\WhatsApp\ProcessWhatsAppWebhookJob;
use App\Models\WhatsApp\WhatsAppWebhookEvent;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookService
{
    /**
     * Verify Meta Webhook subscription handshake (GET).
     */
    public function verifyWebhook(Request $request): ?string
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $configuredToken = config('services.whatsapp.webhook_verify_token') ?: config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && !empty($token) && hash_equals((string) $configuredToken, (string) $token)) {
            return (string) $challenge;
        }

        return null;
    }

    /**
     * Validate Meta X-Hub-Signature-256 header (POST).
     */
    public function validateSignature(string $rawContent, ?string $signatureHeader, ?string $appSecret = null): bool
    {
        if (empty($signatureHeader)) {
            return false;
        }

        $secret = $appSecret ?: config('services.whatsapp.app_secret') ?: config('services.meta.app_secret');
        if (empty($secret)) {
            Log::warning('WhatsApp webhook validation: Missing Meta App Secret in configuration.');
            return false;
        }

        $parts = explode('=', $signatureHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'sha256') {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $rawContent, $secret);
        return hash_equals($expectedHash, $parts[1]);
    }

    /**
     * Ingest and deduplicate webhook event, acknowledging Meta quickly and queueing async processing.
     */
    public function ingestWebhook(array $payload, string $rawContent): array
    {
        // Generate an event ID or hash for deduplication
        $eventId = $this->extractEventId($payload) ?: hash('sha256', $rawContent);

        // Check if event has already been recorded
        $existing = WhatsAppWebhookEvent::where('event_id', $eventId)->first();
        if ($existing) {
            return [
                'status' => 'duplicate',
                'event_id' => $eventId,
                'message' => 'Event already received and processed.',
            ];
        }

        // Determine event type
        $eventType = 'unknown';
        if (isset($payload['entry'][0]['changes'][0]['field'])) {
            $eventType = $payload['entry'][0]['changes'][0]['field'];
        }

        $event = WhatsAppWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
            'received_at' => now(),
        ]);

        // Dispatch background job for async processing
        ProcessWhatsAppWebhookJob::dispatch($event->id);

        return [
            'status' => 'queued',
            'event_id' => $eventId,
        ];
    }

    /**
     * Extract unique message ID or status ID from payload if present.
     */
    protected function extractEventId(array $payload): ?string
    {
        $value = $payload['entry'][0]['changes'][0]['value'] ?? [];
        if (!empty($value['messages'][0]['id'])) {
            return 'msg_' . $value['messages'][0]['id'];
        }
        if (!empty($value['statuses'][0]['id'])) {
            $status = $value['statuses'][0]['status'] ?? 'unknown';
            return 'status_' . $value['statuses'][0]['id'] . '_' . $status;
        }
        return null;
    }
}
