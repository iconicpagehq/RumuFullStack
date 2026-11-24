<?php

namespace App\Jobs\WhatsApp;

use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    protected int $messageId;

    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $message = WhatsAppMessage::with(['conversation.account'])->find($this->messageId);
        if (!$message) {
            Log::warning("SendWhatsAppMessageJob: Message ID {$this->messageId} not found.");
            return;
        }

        $account = $message->conversation?->account;
        if (!$account) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'No connected WhatsApp account associated with conversation.',
                'failed_at' => now(),
            ]);
            return;
        }

        try {
            $response = null;

            if ($message->type === 'template') {
                $payload = $message->raw_payload ?? [];
                $templateName = $payload['template_name'] ?? '';
                $language = $payload['language'] ?? 'en_US';
                $components = $payload['components'] ?? [];

                $response = $whatsAppService->sendTemplateMessage(
                    $account,
                    $message->recipient_id,
                    $templateName,
                    $language,
                    $components,
                    $message
                );
            } elseif (in_array($message->type, ['image', 'video', 'document', 'audio'], true) && $message->media_url) {
                $response = $whatsAppService->sendMediaMessage(
                    $account,
                    $message->recipient_id,
                    $message->type,
                    $message->media_url,
                    $message->content,
                    $message
                );
            } else {
                // Default to text message
                $response = $whatsAppService->sendTextMessage(
                    $account,
                    $message->recipient_id,
                    $message->content ?? '',
                    $message
                );
            }

            $metaMsgId = $response['messages'][0]['id'] ?? null;

            $message->update([
                'status' => 'sent',
                'meta_message_id' => $metaMsgId ?: $message->meta_message_id,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            // Update conversation last message details
            if ($message->conversation) {
                $message->conversation->update([
                    'last_message_text' => $message->content ?: ($message->type . ' attachment'),
                    'last_message_at' => now(),
                    'last_outgoing_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error("SendWhatsAppMessageJob failed for message {$message->id}: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'failed_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = WhatsAppMessage::find($this->messageId);
        if ($message && $message->status !== 'sent') {
            $message->update([
                'status' => 'failed',
                'error_message' => $exception ? $exception->getMessage() : 'Max retries exceeded.',
                'failed_at' => now(),
            ]);
        }
    }
}
