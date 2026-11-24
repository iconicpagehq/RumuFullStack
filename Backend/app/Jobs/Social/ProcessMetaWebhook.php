<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialConversation;
use App\Models\Social\SocialMessage;
use App\Models\Social\SocialPage;
use App\Models\Social\SocialPost;
use App\Models\Social\SocialWebhookEvent;
use App\Services\Social\Meta\MetaPostService;
use App\Services\Social\Meta\MetaMentionService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMetaWebhook implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $webhookEventId;

    public function __construct(int $webhookEventId)
    {
        $this->webhookEventId = $webhookEventId;
    }

    public function handle(MetaPostService $postService, MetaMentionService $mentionService): void
    {
        $event = SocialWebhookEvent::find($this->webhookEventId);
        if (!$event || $event->processed) {
            return;
        }

        try {
            $payload = $event->payload;

            // Handle WhatsApp Business Cloud API incoming webhook
            if ($event->platform === 'whatsapp' || ($payload['object'] ?? '') === 'whatsapp_business_account' || $event->event_type === 'whatsapp_business_account') {
                $this->processWhatsAppWebhook($payload);
                $event->update([
                    'processed' => true,
                    'processed_at' => Carbon::now(),
                ]);
                return;
            }

            $pageExternalId = (string) ($payload['id'] ?? '');

            // Find matching SocialPage
            $page = SocialPage::where('external_id', $pageExternalId)
                ->orWhere('instagram_business_account_id', $pageExternalId)
                ->first();

            if (!$page) {
                // Event for an unlinked or deactivated page
                $event->update([
                    'processed' => true,
                    'processed_at' => Carbon::now(),
                ]);
                return;
            }

            // 1. Process Messaging events (Facebook Messenger & Instagram Direct)
            if (isset($payload['messaging']) && is_array($payload['messaging'])) {
                foreach ($payload['messaging'] as $messagingItem) {
                    $this->processMessagingItem($page, $messagingItem);
                }
            }

            // 2. Process Feed changes (Facebook posts & comments)
            if (isset($payload['changes']) && is_array($payload['changes'])) {
                foreach ($payload['changes'] as $change) {
                    $field = $change['field'] ?? '';
                    $value = $change['value'] ?? [];

                    if ($field === 'feed') {
                        $this->processFeedChange($page, $value, $postService);
                    } elseif ($field === 'comments') {
                        $this->processInstagramCommentChange($page, $value, $postService);
                    } elseif ($field === 'mentions') {
                        $this->processInstagramMentionChange($page, $value, $mentionService);
                    }
                }
            }

            $event->update([
                'processed' => true,
                'processed_at' => Carbon::now(),
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to process Meta webhook event #{$this->webhookEventId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $event->update([
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function processMessagingItem(SocialPage $page, array $item): void
    {
        $senderId = (string) ($item['sender']['id'] ?? '');
        $recipientId = (string) ($item['recipient']['id'] ?? '');
        $isFromPage = ($senderId === $page->external_id || $senderId === $page->instagram_business_account_id);
        $participantId = $isFromPage ? $recipientId : $senderId;

        if (!$participantId) {
            return;
        }

        $messageData = $item['message'] ?? null;
        if (!$messageData) {
            return;
        }

        $messageExternalId = $messageData['mid'] ?? null;
        $messageText = $messageData['text'] ?? '';
        $attachments = $messageData['attachments'] ?? [];
        $timestamp = isset($item['timestamp']) ? Carbon::createFromTimestampMs($item['timestamp']) : Carbon::now();

        // Find or create conversation
        $conversation = SocialConversation::firstOrCreate(
            [
                'social_page_id' => $page->id,
                'participant_id' => $participantId,
            ],
            [
                'company_id' => $page->company_id,
                'user_id' => $page->user_id,
                'platform' => $page->platform,
                'external_id' => "conv_{$page->id}_{$participantId}",
                'participant_name' => "Participant {$participantId}",
                'unread_count' => 0,
            ]
        );

        // Record message
        SocialMessage::updateOrCreate(
            [
                'social_conversation_id' => $conversation->id,
                'external_id' => $messageExternalId,
            ],
            [
                'company_id' => $page->company_id,
                'user_id' => $page->user_id,
                'platform' => $page->platform,
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'message_text' => $messageText,
                'attachments' => $attachments,
                'is_from_page' => $isFromPage,
                'sent_at' => $timestamp,
            ]
        );

        // Update conversation summary
        $conversation->update([
            'last_message_text' => $messageText ?: '[Attachment]',
            'last_message_at' => $timestamp,
            'unread_count' => $isFromPage ? 0 : ($conversation->unread_count + 1),
        ]);
    }

    protected function processFeedChange(SocialPage $page, array $value, MetaPostService $postService): void
    {
        $item = $value['item'] ?? '';
        $verb = $value['verb'] ?? '';

        if ($item === 'comment' && ($verb === 'add' || $verb === 'edit')) {
            $postId = $value['post_id'] ?? null;
            if (!$postId) return;

            $post = SocialPost::where('external_id', $postId)->first();
            if ($post) {
                $postService->syncComment($post, [
                    'id' => $value['comment_id'],
                    'message' => $value['message'] ?? '',
                    'created_time' => isset($value['created_time']) ? Carbon::createFromTimestamp($value['created_time'])->toISOString() : Carbon::now()->toISOString(),
                    'from' => [
                        'id' => $value['from']['id'] ?? null,
                        'name' => $value['from']['name'] ?? 'User',
                    ],
                ]);
            }
        }
    }

    protected function processInstagramCommentChange(SocialPage $page, array $value, MetaPostService $postService): void
    {
        $mediaId = $value['media']['id'] ?? null;
        if (!$mediaId) return;

        $post = SocialPost::where('external_id', $mediaId)->first();
        if ($post) {
            $postService->syncComment($post, [
                'id' => $value['id'],
                'text' => $value['text'] ?? '',
                'timestamp' => Carbon::now()->toISOString(),
                'user' => [
                    'id' => $value['from']['id'] ?? null,
                    'username' => $value['from']['username'] ?? 'User',
                ],
            ]);
        }
    }

    protected function processInstagramMentionChange(SocialPage $page, array $value, MetaMentionService $mentionService): void
    {
        $mediaId = $value['media_id'] ?? null;
        if (!$mediaId) return;

        $mentionService->syncInstagramMention($page, [
            'id' => $mediaId,
            'caption' => $value['caption'] ?? null,
            'timestamp' => Carbon::now()->toISOString(),
            'media_type' => 'post',
        ]);
    }

    /**
     * Ingest and thread incoming WhatsApp Business Cloud API messages.
     */
    protected function processWhatsAppWebhook(array $payload): void
    {
        $wabaId = (string) ($payload['id'] ?? '');
        $changes = $payload['changes'] ?? [];

        foreach ($changes as $change) {
            $field = $change['field'] ?? '';
            $value = $change['value'] ?? [];

            if ($field !== 'messages') {
                continue;
            }

            $metadata = $value['metadata'] ?? [];
            $phoneNumberId = (string) ($metadata['phone_number_id'] ?? '');
            $displayPhoneNumber = (string) ($metadata['display_phone_number'] ?? 'WhatsApp Business');

            // Collect sender profiles from contacts
            $contacts = [];
            if (!empty($value['contacts']) && is_array($value['contacts'])) {
                foreach ($value['contacts'] as $c) {
                    $waId = $c['wa_id'] ?? null;
                    if ($waId) {
                        $contacts[$waId] = $c['profile']['name'] ?? null;
                    }
                }
            }

            // Find or create WhatsApp SocialPage
            $page = SocialPage::where('platform', 'whatsapp')
                ->where(function ($q) use ($phoneNumberId, $wabaId) {
                    if ($phoneNumberId) $q->where('external_id', $phoneNumberId);
                    if ($wabaId) $q->orWhere('external_id', $wabaId);
                })
                ->first();

            if (!$page) {
                // Find existing social account or page to inherit tenancy
                $parentPage = SocialPage::first();
                $socialAccount = \App\Models\Social\SocialAccount::first();

                $companyId = $parentPage ? $parentPage->company_id : ($socialAccount ? $socialAccount->company_id : null);
                $userId = $parentPage ? $parentPage->user_id : ($socialAccount ? $socialAccount->user_id : 1);
                $accountId = $socialAccount ? $socialAccount->id : ($parentPage ? $parentPage->social_account_id : 1);
                $token = $parentPage ? $parentPage->access_token : (config('services.meta.capi_access_token') ?: 'wa_token');

                $page = SocialPage::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'social_account_id' => $accountId,
                    'platform' => 'whatsapp',
                    'external_id' => $phoneNumberId ?: ($wabaId ?: 'whatsapp_default'),
                    'name' => "WhatsApp ({$displayPhoneNumber})",
                    'username' => $displayPhoneNumber,
                    'category' => 'WhatsApp Business Account',
                    'access_token' => $token,
                    'is_active' => true,
                    'last_synced_at' => Carbon::now(),
                ]);
            }

            $messages = $value['messages'] ?? [];
            foreach ($messages as $msg) {
                $senderPhone = (string) ($msg['from'] ?? '');
                $messageExternalId = (string) ($msg['id'] ?? '');
                $msgType = $msg['type'] ?? 'text';
                $timestamp = isset($msg['timestamp']) ? Carbon::createFromTimestamp($msg['timestamp']) : Carbon::now();

                $messageText = '';
                $attachments = [];

                if ($msgType === 'text') {
                    $messageText = $msg['text']['body'] ?? '';
                } elseif ($msgType === 'image') {
                    $messageText = $msg['image']['caption'] ?? '[Photo]';
                    $attachments[] = ['type' => 'image', 'id' => $msg['image']['id'] ?? null];
                } elseif ($msgType === 'video') {
                    $messageText = $msg['video']['caption'] ?? '[Video]';
                    $attachments[] = ['type' => 'video', 'id' => $msg['video']['id'] ?? null];
                } elseif ($msgType === 'document') {
                    $messageText = $msg['document']['caption'] ?? ($msg['document']['filename'] ?? '[Document]');
                    $attachments[] = ['type' => 'document', 'id' => $msg['document']['id'] ?? null, 'filename' => $msg['document']['filename'] ?? null];
                } elseif ($msgType === 'location') {
                    $loc = $msg['location'] ?? [];
                    $locName = $loc['name'] ?? "{$loc['latitude']}, {$loc['longitude']}";
                    $messageText = "📍 Location: {$locName}";
                } elseif ($msgType === 'button') {
                    $messageText = $msg['button']['text'] ?? '[Button Response]';
                } elseif ($msgType === 'interactive') {
                    $interactive = $msg['interactive'] ?? [];
                    $messageText = $interactive['button_reply']['title'] ?? ($interactive['list_reply']['title'] ?? '[Interactive Reply]');
                } else {
                    $messageText = "[{$msgType}]";
                }

                $senderName = $contacts[$senderPhone] ?? "+{$senderPhone}";

                // Find or create conversation
                $conversation = SocialConversation::firstOrCreate(
                    [
                        'social_page_id' => $page->id,
                        'participant_id' => $senderPhone,
                    ],
                    [
                        'company_id' => $page->company_id,
                        'user_id' => $page->user_id,
                        'platform' => 'whatsapp',
                        'external_id' => "wa_conv_{$page->id}_{$senderPhone}",
                        'participant_name' => $senderName,
                        'participant_username' => $senderPhone,
                        'unread_count' => 0,
                    ]
                );

                // Update participant name if known
                if (!empty($contacts[$senderPhone]) && $conversation->participant_name !== $contacts[$senderPhone]) {
                    $conversation->update(['participant_name' => $contacts[$senderPhone]]);
                }

                // Record message
                SocialMessage::updateOrCreate(
                    [
                        'social_conversation_id' => $conversation->id,
                        'external_id' => $messageExternalId,
                    ],
                    [
                        'company_id' => $page->company_id,
                        'user_id' => $page->user_id,
                        'platform' => 'whatsapp',
                        'sender_id' => $senderPhone,
                        'sender_name' => $senderName,
                        'recipient_id' => $phoneNumberId ?: $page->external_id,
                        'message_text' => $messageText,
                        'attachments' => $attachments,
                        'is_from_page' => false,
                        'sent_at' => $timestamp,
                    ]
                );

                // Update conversation summary
                $conversation->update([
                    'last_message_text' => $messageText ?: '[Message]',
                    'last_message_at' => $timestamp,
                    'unread_count' => $conversation->unread_count + 1,
                ]);
            }
        }
    }
}

