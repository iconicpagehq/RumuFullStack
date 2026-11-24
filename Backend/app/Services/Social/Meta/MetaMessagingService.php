<?php

namespace App\Services\Social\Meta;

class MetaMessagingService
{
    protected MetaApiClient $client;

    public function __construct(MetaApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve conversations for a Facebook Page or Instagram Business account.
     */
    public function getConversations(
        string $pageExternalId,
        string $accessToken,
        string $platform = 'facebook',
        int $limit = 25,
        ?string $after = null
    ): array {
        $query = [
            'fields' => 'id,snippet,updated_time,unread_count,senders,participants',
            'limit' => $limit,
        ];

        if ($platform === 'instagram') {
            $query['platform'] = 'instagram';
        }

        if ($after) {
            $query['after'] = $after;
        }

        return $this->client->get("/{$pageExternalId}/conversations", $query, $accessToken);
    }

    /**
     * Retrieve message thread history for a given conversation.
     */
    public function getMessages(string $conversationExternalId, string $accessToken, int $limit = 50, ?string $after = null): array
    {
        $query = [
            'fields' => 'id,message,created_time,from,to,attachments{id,mime_type,name,size,image_data,video_data,file_url},shares',
            'limit' => $limit,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        return $this->client->get("/{$conversationExternalId}/messages", $query, $accessToken);
    }

    /**
     * Send a direct message or Messenger reply to a participant.
     */
    public function sendMessage(
        string $pageExternalId,
        string $recipientId,
        string $messageText,
        string $accessToken,
        ?array $attachment = null
    ): array {
        $payload = [
            'recipient' => [
                'id' => $recipientId,
            ],
            'message' => [
                'text' => $messageText,
            ],
        ];

        if ($attachment) {
            $payload['message']['attachment'] = $attachment;
        }

        return $this->client->post("/{$pageExternalId}/messages", $payload, $accessToken);
    }

    /**
     * Retrieve participant profile details (display name, username, profile picture).
     */
    public function getParticipantProfile(string $participantId, string $accessToken, string $platform = 'facebook'): ?array
    {
        try {
            $fields = ($platform === 'instagram') ? 'name,username,profile_pic' : 'name,profile_pic';
            return $this->client->get("/{$participantId}", ['fields' => $fields], $accessToken);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

