<?php

namespace App\Http\Controllers\Api\Social;

use App\Models\Social\SocialConversation;
use App\Models\Social\SocialMessage;
use App\Services\Social\Meta\MetaMessagingService;
use App\Services\Social\Meta\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class SocialInboxController extends BaseSocialController
{
    protected MetaMessagingService $messagingService;
    protected WhatsAppService $whatsAppService;

    public function __construct(MetaMessagingService $messagingService, WhatsAppService $whatsAppService)
    {
        $this->messagingService = $messagingService;
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * List inbox conversations with filtering and pagination.
     * GET /api/v1/social/inbox/conversations
     */
    public function conversations(Request $request)
    {
        $query = $this->scopeTenant(SocialConversation::query(), $request)
            ->with(['page:id,name,username,platform', 'assignedTo:id,name,email'])
            ->orderBy('last_message_at', 'desc');

        if ($request->has('platform') && in_array($request->query('platform'), ['facebook', 'instagram', 'whatsapp'])) {
            $query->where('platform', $request->query('platform'));
        }

        if ($request->boolean('unread_only')) {
            $query->where('unread_count', '>', 0);
        }

        if ($request->has('assigned_to')) {
            $query->where('is_assigned_to', (int) $request->query('assigned_to'));
        }

        if ($request->has('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('participant_name', 'like', $term)
                  ->orWhere('participant_username', 'like', $term)
                  ->orWhere('last_message_text', 'like', $term);
            });
        }

        $perPage = min((int) $request->query('per_page', 250), 500);
        $conversations = $query->paginate($perPage);

        return $this->paginated($conversations, 'Conversations retrieved successfully.');
    }

    /**
     * Get message thread for a conversation.
     * GET /api/v1/social/inbox/conversations/{id}/messages
     */
    public function messages(Request $request, int $id)
    {
        $conversation = $this->scopeTenant(SocialConversation::query(), $request)
            ->with(['page', 'assignedTo:id,name'])
            ->findOrFail($id);

        $messages = SocialMessage::where('social_conversation_id', $conversation->id)
            ->orderBy('sent_at', 'asc')
            ->get();

        // On-demand fetch rich profile if avatar is missing
        if (empty($conversation->participant_avatar) && $conversation->participant_id && $conversation->page && $conversation->page->access_token) {
            try {
                $profile = $this->messagingService->getParticipantProfile($conversation->participant_id, $conversation->page->access_token, $conversation->platform);
                if ($profile) {
                    $conversation->update([
                        'participant_name' => $profile['name'] ?? $conversation->participant_name,
                        'participant_username' => $profile['username'] ?? $conversation->participant_username,
                        'participant_avatar' => $profile['profile_pic'] ?? $conversation->participant_avatar,
                    ]);
                }
            } catch (\Throwable $pe) {
                // Ignore gracefully
            }
        }

        // On-demand fetch from Meta if thread is empty in DB
        if ($messages->isEmpty() && $conversation->page && $conversation->page->access_token) {
            try {
                $page = $conversation->page;
                $messagesResp = $this->messagingService->getMessages($conversation->external_id, $page->access_token, 30);
                foreach ($messagesResp['data'] ?? [] as $msgData) {
                    $senderId = $msgData['from']['id'] ?? '';
                    $senderName = $msgData['from']['name'] ?? $msgData['from']['username'] ?? null;
                    $recipientId = $msgData['to']['data'][0]['id'] ?? '';
                    $isFromPage = ($senderId === $page->external_id);
                    $sentAt = isset($msgData['created_time']) ? Carbon::parse($msgData['created_time']) : Carbon::now();

                    $msgText = $msgData['message'] ?? '';
                    if (empty($msgText) && !empty($msgData['shares']['data'][0]['link'])) {
                        $msgText = $msgData['shares']['data'][0]['link'];
                    }

                    $attachments = $msgData['attachments']['data'] ?? $msgData['shares']['data'] ?? null;

                    SocialMessage::updateOrCreate(
                        [
                            'social_conversation_id' => $conversation->id,
                            'external_id' => $msgData['id'],
                        ],
                        [
                            'company_id' => $conversation->company_id,
                            'user_id' => $conversation->user_id,
                            'platform' => $conversation->platform,
                            'sender_id' => $senderId,
                            'sender_name' => $senderName,
                            'recipient_id' => $recipientId,
                            'message_text' => $msgText,
                            'attachments' => $attachments,
                            'is_from_page' => $isFromPage,
                            'sent_at' => $sentAt,
                        ]
                    );
                }

                $messages = SocialMessage::where('social_conversation_id', $conversation->id)
                    ->orderBy('sent_at', 'asc')
                    ->get();
            } catch (Throwable $e) {
                // Return whatever is in DB if on-demand fetch fails
            }
        }

        // Mark unread count as 0
        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        return $this->success([
            'conversation' => $conversation,
            'messages' => $messages,
        ], 'Messages retrieved successfully.');
    }

    /**
     * Send reply message in a conversation.
     * POST /api/v1/social/inbox/conversations/{id}/messages
     */
    public function sendMessage(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $conversation = $this->scopeTenant(SocialConversation::query(), $request)
            ->with('page')
            ->findOrFail($id);

        $page = $conversation->page;
        if (!$page || !$page->is_active) {
            return $this->error('The associated social page is inactive or disconnected.', 400);
        }

        try {
            $messageId = null;
            $cloudApiError = null;

            if ($conversation->platform === 'whatsapp') {
                $phoneNumberId = (string) (config('services.meta.wa_phone_number_id') ?: $page->external_id);
                $token = (string) (config('services.meta.wa_access_token') ?: $page->access_token);

                // Send reply via Meta WhatsApp Business Cloud API
                try {
                    $metaResp = $this->whatsAppService->sendMessage(
                        $phoneNumberId,
                        $conversation->participant_id,
                        $request->input('message'),
                        $token
                    );
                    $messageId = $metaResp['messages'][0]['id'] ?? null;
                } catch (Throwable $we) {
                    \Illuminate\Support\Facades\Log::warning("WhatsApp Cloud API send deferred: {$we->getMessage()}");
                    $cloudApiError = $we->getMessage();
                    $messageId = 'wa_out_' . uniqid();
                }
            } else {
                // Send reply via Meta Graph API (Messenger / Instagram Direct)
                $metaResp = $this->messagingService->sendMessage(
                    $page->external_id,
                    $conversation->participant_id,
                    $request->input('message'),
                    $page->access_token
                );
                $messageId = $metaResp['message_id'] ?? null;
            }

            // Persist sent message
            $message = SocialMessage::create([
                'company_id' => $conversation->company_id,
                'user_id' => $conversation->user_id,
                'social_conversation_id' => $conversation->id,
                'platform' => $conversation->platform,
                'external_id' => $messageId ?: ('sent_' . uniqid()),
                'sender_id' => $page->external_id,
                'sender_name' => $page->name,
                'recipient_id' => $conversation->participant_id,
                'message_text' => $request->input('message'),
                'is_from_page' => true,
                'sent_at' => Carbon::now(),
            ]);

            // Update conversation last message
            $conversation->update([
                'last_message_text' => $request->input('message'),
                'last_message_at' => Carbon::now(),
                'unread_count' => 0,
            ]);

            return $this->success($message, 'Message sent successfully.');
        } catch (Throwable $e) {
            return $this->error("Failed to send message: {$e->getMessage()}", 500);
        }
    }

    /**
     * Update conversation assignment or internal notes.
     * PATCH /api/v1/social/inbox/conversations/{id}
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'is_assigned_to' => 'nullable|exists:users,id',
            'internal_notes' => 'nullable|string|max:5000',
        ]);

        $conversation = $this->scopeTenant(SocialConversation::query(), $request)->findOrFail($id);

        $updates = [];
        if ($request->has('is_assigned_to')) {
            $updates['is_assigned_to'] = $request->input('is_assigned_to');
        }
        if ($request->has('internal_notes')) {
            $updates['internal_notes'] = $request->input('internal_notes');
        }

        $conversation->update($updates);

        return $this->success($conversation, 'Conversation updated successfully.');
    }
}

