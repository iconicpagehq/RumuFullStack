<?php

namespace App\Jobs\WhatsApp;

use App\Models\Lead;
use App\Models\WhatsApp\WhatsAppAccount;
use App\Models\WhatsApp\WhatsAppContact;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppWebhookEvent;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 15, 30];

    protected int $webhookEventId;

    public function __construct(int $webhookEventId)
    {
        $this->webhookEventId = $webhookEventId;
    }

    public function handle(): void
    {
        $event = WhatsAppWebhookEvent::find($this->webhookEventId);
        if (!$event || $event->status === 'processed') {
            return;
        }

        $event->update(['status' => 'processing']);

        try {
            $payload = $event->payload ?? [];
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    $field = $change['field'] ?? '';

                    if ($field !== 'messages') {
                        continue;
                    }

                    $metadata = $value['metadata'] ?? [];
                    $phoneNumberId = $metadata['phone_number_id'] ?? null;

                    // Match connected account by phone_number_id
                    $account = $phoneNumberId ? WhatsAppAccount::where('phone_number_id', $phoneNumberId)->first() : null;

                    // 1. Process Message Status Updates (sent, delivered, read, failed)
                    if (!empty($value['statuses'])) {
                        foreach ($value['statuses'] as $statusData) {
                            $this->processStatusUpdate($statusData);
                        }
                    }

                    // 2. Process Incoming Messages
                    if (!empty($value['messages']) && $account) {
                        $contactsData = $value['contacts'] ?? [];
                        foreach ($value['messages'] as $messageData) {
                            $this->processIncomingMessage($account, $messageData, $contactsData);
                        }
                    }
                }
            }

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error("ProcessWhatsAppWebhookJob failed for event {$this->webhookEventId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $event->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process an incoming customer message.
     */
    protected function processIncomingMessage(WhatsAppAccount $account, array $messageData, array $contactsData): void
    {
        $metaMsgId = $messageData['id'] ?? null;
        if (!$metaMsgId) {
            return;
        }

        // Idempotency check: don't insert duplicate message
        if (WhatsAppMessage::where('meta_message_id', $metaMsgId)->exists()) {
            return;
        }

        $fromWaId = $messageData['from'] ?? null;
        if (!$fromWaId) {
            return;
        }

        // Extract contact profile name from contacts block
        $profileName = null;
        foreach ($contactsData as $c) {
            if (($c['wa_id'] ?? '') === $fromWaId) {
                $profileName = $c['profile']['name'] ?? null;
                break;
            }
        }

        // Find or create WhatsAppContact with company isolation
        $contact = WhatsAppContact::firstOrCreate(
            [
                'company_id' => $account->company_id,
                'wa_id' => $fromWaId,
            ],
            [
                'phone_number' => '+' . ltrim($fromWaId, '+'),
                'name' => $profileName,
                'last_active_at' => now(),
            ]
        );

        if ($profileName && empty($contact->name)) {
            $contact->update(['name' => $profileName]);
        }

        // Automatically associate with existing CRM Lead if matching phone
        if (!$contact->lead_id) {
            $lead = Lead::where('enterprise_id', $account->company_id)
                ->where(function ($q) use ($fromWaId) {
                    $q->where('phone', 'like', "%{$fromWaId}%")
                      ->orWhere('phone', 'like', '%' . substr($fromWaId, -10) . '%');
                })->first();

            if ($lead) {
                $contact->update(['lead_id' => $lead->id]);
            }
        }

        // Find or create WhatsAppConversation
        $conversation = WhatsAppConversation::firstOrCreate(
            [
                'whatsapp_account_id' => $account->id,
                'whatsapp_contact_id' => $contact->id,
            ],
            [
                'company_id' => $account->company_id,
                'status' => 'open',
                'session_expires_at' => now()->addHours(24),
                'unread_count' => 0,
            ]
        );

        // Determine message type and extract content
        $msgType = $messageData['type'] ?? 'text';
        $content = null;
        $mediaUrl = null;
        $mediaMimeType = null;

        if ($msgType === 'text') {
            $content = $messageData['text']['body'] ?? '';
        } elseif (in_array($msgType, ['image', 'video', 'document', 'audio', 'sticker'], true)) {
            $mediaObj = $messageData[$msgType] ?? [];
            $mediaUrl = $mediaObj['link'] ?? ($mediaObj['id'] ?? null);
            $mediaMimeType = $mediaObj['mime_type'] ?? null;
            $content = $mediaObj['caption'] ?? "[{$msgType}]";
        } elseif ($msgType === 'button') {
            $content = $messageData['button']['text'] ?? '[button]';
        } elseif ($msgType === 'interactive') {
            $interactive = $messageData['interactive'] ?? [];
            $content = $interactive['button_reply']['title'] ?? ($interactive['list_reply']['title'] ?? '[interactive]');
        } else {
            $content = "[{$msgType}]";
        }

        $sentTimestamp = isset($messageData['timestamp'])
            ? Carbon::createFromTimestamp((int) $messageData['timestamp'])
            : now();

        // Save incoming message
        WhatsAppMessage::create([
            'company_id' => $account->company_id,
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => $metaMsgId,
            'direction' => 'incoming',
            'sender_id' => $fromWaId,
            'recipient_id' => $account->phone_number_id,
            'type' => $msgType,
            'content' => $content,
            'media_url' => $mediaUrl,
            'media_mime_type' => $mediaMimeType,
            'raw_payload' => $messageData,
            'status' => 'delivered',
            'sent_at' => $sentTimestamp,
            'delivered_at' => now(),
        ]);

        // Update conversation details and refresh 24h customer service window
        $conversation->update([
            'last_message_text' => $content,
            'last_message_at' => $sentTimestamp,
            'last_incoming_at' => $sentTimestamp,
            'session_expires_at' => now()->addHours(24),
            'unread_count' => $conversation->unread_count + 1,
            'status' => 'open',
        ]);

        $contact->update(['last_active_at' => now()]);
    }

    /**
     * Process message delivery status updates (sent, delivered, read, failed).
     */
    protected function processStatusUpdate(array $statusData): void
    {
        $metaMsgId = $statusData['id'] ?? null;
        $status = $statusData['status'] ?? null;
        if (!$metaMsgId || !$status) {
            return;
        }

        $message = WhatsAppMessage::where('meta_message_id', $metaMsgId)->first();
        if (!$message) {
            return;
        }

        $timestamp = isset($statusData['timestamp'])
            ? Carbon::createFromTimestamp((int) $statusData['timestamp'])
            : now();

        $updateData = ['status' => $status];

        switch ($status) {
            case 'sent':
                $updateData['sent_at'] = $message->sent_at ?: $timestamp;
                break;
            case 'delivered':
                $updateData['delivered_at'] = $message->delivered_at ?: $timestamp;
                break;
            case 'read':
                $updateData['read_at'] = $message->read_at ?: $timestamp;
                break;
            case 'failed':
                $updateData['failed_at'] = $timestamp;
                $errors = $statusData['errors'] ?? [];
                if (!empty($errors)) {
                    $updateData['error_code'] = (string) ($errors[0]['code'] ?? '');
                    $updateData['error_message'] = (string) ($errors[0]['title'] ?? ($errors[0]['message'] ?? 'Delivery failed'));
                }
                break;
        }

        $message->update($updateData);
    }
}
