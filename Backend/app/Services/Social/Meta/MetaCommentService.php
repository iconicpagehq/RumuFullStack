<?php

namespace App\Services\Social\Meta;

class MetaCommentService
{
    protected MetaApiClient $client;

    public function __construct(MetaApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve comments for a post or media item.
     */
    public function getComments(string $externalPostId, string $accessToken, int $limit = 50, ?string $after = null, ?string $platform = null): array
    {
        if ($platform === 'instagram') {
            $fields = 'id,text,timestamp,like_count,username,replies{id,text,timestamp,username}';
        } elseif ($platform === 'facebook') {
            $fields = 'id,message,created_time,like_count,comments{id,message,created_time}';
        } else {
            $fields = 'id,from,message,text,created_time,timestamp,like_count,user,username,comments{id,from,message,text,created_time,timestamp,username}';
        }

        $query = [
            'fields' => $fields,
            'limit' => $limit,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        return $this->client->get("/{$externalPostId}/comments", $query, $accessToken);
    }

    /**
     * Post a reply to a comment or top-level post comment.
     */
    public function replyToComment(string $commentExternalId, string $message, string $accessToken): array
    {
        return $this->client->post("/{$commentExternalId}/comments", [
            'message' => $message,
        ], $accessToken);
    }

    /**
     * Hide or unhide a comment (Facebook & Instagram moderation).
     */
    public function setCommentHidden(string $commentExternalId, bool $hide, string $accessToken): array
    {
        return $this->client->post("/{$commentExternalId}", [
            'is_hidden' => $hide,
        ], $accessToken);
    }

    /**
     * Delete a comment where permitted.
     */
    public function deleteComment(string $commentExternalId, string $accessToken): array
    {
        return $this->client->delete("/{$commentExternalId}", [], $accessToken);
    }
}

