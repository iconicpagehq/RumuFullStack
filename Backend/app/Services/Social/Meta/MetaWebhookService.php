<?php

namespace App\Services\Social\Meta;

use App\Jobs\Social\ProcessMetaWebhook;
use App\Models\Social\SocialWebhookEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class MetaWebhookService
{
    protected string $appSecret;
    protected string $verifyToken;

    public function __construct()
    {
        $this->appSecret = (string) config('services.meta.app_secret');
        $this->verifyToken = (string) (config('services.meta.webhook_verify_token') ?: env('META_WEBHOOK_VERIFY_TOKEN', ''));
    }

    /**
     * Handle Meta Webhook verification handshake.
     */
    public function verifyWebhook(string $mode, string $token, string $challenge): string
    {
        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            return $challenge;
        }

        throw new Exception('Invalid webhook verify token or mode.', 403);
    }

    /**
     * Verify X-Hub-Signature-256 using App Secret and raw body.
     */
    public function validateSignature(string $rawContent, ?string $signatureHeader): bool
    {
        if (!$signatureHeader || !str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $rawContent, $this->appSecret);
        $actualSignature = substr($signatureHeader, 7);

        return hash_equals($expectedSignature, $actualSignature);
    }

    /**
     * Ingest incoming webhook payload, persist event, and dispatch background processing.
     */
    public function ingestPayload(array $payload): void
    {
        $object = $payload['object'] ?? 'meta';
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $entryId = $entry['id'] ?? Str::uuid()->toString();
            $time = $entry['time'] ?? time();
            $uniqueEventId = "{$object}_{$entryId}_{$time}_" . Str::random(8);

            $platform = 'facebook';
            if ($object === 'instagram') {
                $platform = 'instagram';
            } elseif ($object === 'whatsapp_business_account') {
                $platform = 'whatsapp';
            }

            $event = SocialWebhookEvent::create([
                'event_id' => $uniqueEventId,
                'platform' => $platform,
                'event_type' => $object,
                'payload' => $entry,
                'processed' => false,
            ]);

            // Dispatch async job
            ProcessMetaWebhook::dispatch($event->id);
        }
    }
}

