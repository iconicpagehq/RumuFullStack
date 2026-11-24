<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialConversation;
use App\Models\Social\SocialMessage;
use App\Models\Social\SocialPage;
use App\Services\Social\Meta\MetaMessagingService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSocialConversations implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $socialPageId;

    public function __construct(int $socialPageId)
    {
        $this->socialPageId = $socialPageId;
    }

    public function handle(MetaMessagingService $messagingService): void
    {
        $page = SocialPage::find($this->socialPageId);
        if (!$page || !$page->is_active) {
            return;
        }

        try {
            $conversationsData = [];
            $after = null;
            $maxPages = 5; // up to 250 conversations across all pages (covers all 154 conversations)
            $pageIdx = 0;

            do {
                $pageIdx++;
                $response = $messagingService->getConversations(
                    $page->external_id,
                    $page->access_token,
                    $page->platform,
                    50,
                    $after
                );

                $batch = $response['data'] ?? [];
                $conversationsData = array_merge($conversationsData, $batch);
                $after = $response['paging']['cursors']['after'] ?? null;
                $hasNext = !empty($response['paging']['next']);
            } while ($after && $hasNext && $pageIdx < $maxPages);

            $profileCache = [];

            $convIdx = 0;
            foreach ($conversationsData as $convData) {
                $convIdx++;
                $convExternalId = $convData['id'];
                $snippet = $convData['snippet'] ?? null;
                $updatedTime = isset($convData['updated_time']) ? Carbon::parse($convData['updated_time']) : Carbon::now();
                $unreadCount = $convData['unread_count'] ?? 0;

                // Identify participant
                $participants = $convData['participants']['data'] ?? $convData['senders']['data'] ?? [];
                $participantId = null;
                $participantName = null;
                $participantUsername = null;
                $participantAvatar = null;

                foreach ($participants as $p) {
                    if (($p['id'] ?? '') !== $page->external_id) {
                        $participantId = $p['id'];
                        $participantUsername = $p['username'] ?? null;
                        $participantName = $p['name'] ?? null;
                        break;
                    }
                }

                if (!$participantId && !empty($participants)) {
                    $participantId = $participants[0]['id'] ?? $convExternalId;
                    $participantUsername = $participants[0]['username'] ?? null;
                    $participantName = $participants[0]['name'] ?? null;
                }

                if (!$participantId) {
                    $participantId = $convExternalId;
                }

                // Retrieve rich profile for participant if available (top 40 conversations upfront)
                if ($participantId && $participantId !== $page->external_id) {
                    if (isset($profileCache[$participantId])) {
                        $cached = $profileCache[$participantId];
                        $participantName = $cached['name'] ?? $participantName;
                        $participantUsername = $cached['username'] ?? $participantUsername;
                        $participantAvatar = $cached['profile_pic'] ?? null;
                    } elseif ($convIdx <= 40) {
                        $profile = $messagingService->getParticipantProfile($participantId, $page->access_token, $page->platform);
                        if ($profile) {
                            $profileCache[$participantId] = $profile;
                            $participantName = $profile['name'] ?? $participantName;
                            $participantUsername = $profile['username'] ?? $participantUsername;
                            $participantAvatar = $profile['profile_pic'] ?? null;
                        }
                    }
                }

                if (empty($participantName)) {
                    $participantName = $participantUsername ? "@{$participantUsername}" : ($page->platform === 'instagram' ? 'Instagram User' : 'User');
                }

                // Fetch message thread for top 5 conversations upfront (older conversations load on-demand when clicked)
                $messagesData = [];
                if ($convIdx <= 5) {
                    try {
                        $messagesResp = $messagingService->getMessages($convExternalId, $page->access_token, 25);
                        $messagesData = $messagesResp['data'] ?? [];
                    } catch (Throwable $me) {
                        // Fallback safely if thread fetch fails
                    }
                }

                // For Instagram or conversations without snippet, fallback snippet to first message or shared link
                if (empty($snippet) && !empty($messagesData)) {
                    foreach ($messagesData as $msg) {
                        if (!empty($msg['message'])) {
                            $snippet = $msg['message'];
                            break;
                        } elseif (!empty($msg['shares']['data'][0]['link'])) {
                            $snippet = 'Shared Reel: ' . $msg['shares']['data'][0]['link'];
                            break;
                        } elseif (!empty($msg['attachments']['data'])) {
                            $snippet = '[Attachment]';
                            break;
                        }
                    }
                }

                if (empty($snippet)) {
                    $snippet = $page->platform === 'instagram' ? 'Direct message' : 'Messenger chat';
                }

                $existingConv = SocialConversation::where('social_page_id', $page->id)
                    ->where('external_id', $convExternalId)
                    ->first();
                $finalAvatar = $participantAvatar ?: ($existingConv?->participant_avatar);
                $finalName = ($participantName && !str_starts_with($participantName, '@')) 
                    ? $participantName 
                    : (($existingConv && !empty($existingConv->participant_name) && !str_starts_with($existingConv->participant_name, '@')) 
                        ? $existingConv->participant_name 
                        : $participantName);

                $conversation = SocialConversation::updateOrCreate(
                    [
                        'social_page_id' => $page->id,
                        'external_id' => $convExternalId,
                    ],
                    [
                        'company_id' => $page->company_id,
                        'user_id' => $page->user_id,
                        'platform' => $page->platform,
                        'participant_id' => $participantId,
                        'participant_name' => $finalName,
                        'participant_username' => $participantUsername,
                        'participant_avatar' => $finalAvatar,
                        'unread_count' => $unreadCount,
                        'last_message_text' => $snippet,
                        'last_message_at' => $updatedTime,
                    ]
                );

                foreach ($messagesData as $msgData) {
                    $msgExternalId = $msgData['id'];
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
                            'external_id' => $msgExternalId,
                        ],
                        [
                            'company_id' => $page->company_id,
                            'user_id' => $page->user_id,
                            'platform' => $page->platform,
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
            }
        } catch (Throwable $e) {
            Log::error("Failed to sync conversations for page #{$this->socialPageId}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

