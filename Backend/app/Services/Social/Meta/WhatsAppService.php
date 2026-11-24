<?php

namespace App\Services\Social\Meta;

use Exception;

class WhatsAppService
{
    protected MetaApiClient $client;

    public function __construct(MetaApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send a WhatsApp message to a customer via Meta WhatsApp Business Cloud API.
     *
     * @param string $phoneNumberId The Sender Phone Number ID (from Meta Developer Console / WABA)
     * @param string $to Recipient phone number in E.164 format (without + or with digits only)
     * @param string $messageText Body text to send
     * @param string|null $accessToken Optional token (defaults to system token if null)
     * @return array Meta response payload
     */
    public function sendMessage(
        string $phoneNumberId,
        string $to,
        string $messageText,
        ?string $accessToken = null
    ): array {
        // Normalize phone number (strip '+', spaces, dashes, brackets)
        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $messageText,
            ],
        ];

        return $this->client->post("/{$phoneNumberId}/messages", $payload, $accessToken);
    }

    /**
     * Send media message (image, document, audio, video) via WhatsApp Business Cloud API.
     */
    public function sendMediaMessage(
        string $phoneNumberId,
        string $to,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null,
        ?string $accessToken = null
    ): array {
        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        $mediaPayload = [
            'link' => $mediaUrl,
        ];

        if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
            $mediaPayload['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ];

        return $this->client->post("/{$phoneNumberId}/messages", $payload, $accessToken);
    }

    /**
     * Retrieve phone numbers associated with a WhatsApp Business Account (WABA).
     */
    public function getBusinessPhoneNumbers(string $wabaId, string $accessToken): array
    {
        return $this->client->get("/{$wabaId}/phone_numbers", [
            'fields' => 'id,display_phone_number,verified_name,code_verification_status,quality_rating',
        ], $accessToken);
    }
}
