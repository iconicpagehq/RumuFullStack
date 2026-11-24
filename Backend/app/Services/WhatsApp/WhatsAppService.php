<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WhatsAppAccount;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppMessageLog;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl = 'https://graph.facebook.com';

    /**
     * Resolve API version for an account or system default.
     */
    public function getApiVersion(?WhatsAppAccount $account = null): string
    {
        return $account?->api_version ?: config('services.whatsapp.api_version', 'v19.0');
    }

    /**
     * Resolve access token for an account or system default.
     */
    public function getAccessToken(?WhatsAppAccount $account = null): string
    {
        $token = $account?->access_token ?: config('services.whatsapp.access_token');
        if (empty($token)) {
            throw new Exception('WhatsApp Access Token is not configured for this account or system.');
        }
        return $token;
    }

    /**
     * Resolve Phone Number ID for an account or system default.
     */
    public function getPhoneNumberId(?WhatsAppAccount $account = null): string
    {
        $phoneId = $account?->phone_number_id ?: config('services.whatsapp.phone_number_id');
        if (empty($phoneId)) {
            throw new Exception('WhatsApp Phone Number ID is not configured.');
        }
        return $phoneId;
    }

    /**
     * Send a text message via Meta WhatsApp Business Cloud API.
     */
    public function sendTextMessage(
        WhatsAppAccount $account,
        string $to,
        string $text,
        ?WhatsAppMessage $messageModel = null
    ): array {
        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];

        return $this->executePost($account, "/{$this->getPhoneNumberId($account)}/messages", $payload, $messageModel);
    }

    /**
     * Send a media message (image, document, audio, video) via WhatsApp Business Cloud API.
     */
    public function sendMediaMessage(
        WhatsAppAccount $account,
        string $to,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null,
        ?WhatsAppMessage $messageModel = null
    ): array {
        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        $mediaPayload = [
            'link' => $mediaUrl,
        ];

        if ($caption && in_array($mediaType, ['image', 'video', 'document'], true)) {
            $mediaPayload['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ];

        return $this->executePost($account, "/{$this->getPhoneNumberId($account)}/messages", $payload, $messageModel);
    }

    /**
     * Send a template message via WhatsApp Business Cloud API.
     */
    public function sendTemplateMessage(
        WhatsAppAccount $account,
        string $to,
        string $templateName,
        string $languageCode = 'en_US',
        array $components = [],
        ?WhatsAppMessage $messageModel = null
    ): array {
        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->executePost($account, "/{$this->getPhoneNumberId($account)}/messages", $payload, $messageModel);
    }

    /**
     * Fetch WhatsApp Business phone numbers.
     */
    public function getBusinessPhoneNumbers(string $wabaId, ?WhatsAppAccount $account = null): array
    {
        $version = $this->getApiVersion($account);
        $url = "{$this->baseUrl}/{$version}/{$wabaId}/phone_numbers";
        $token = $this->getAccessToken($account);

        $response = Http::withToken($token)
            ->timeout(20)
            ->get($url, [
                'fields' => 'id,display_phone_number,verified_name,code_verification_status,quality_rating',
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch WhatsApp phone numbers: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch message templates from Meta WABA.
     */
    public function getMessageTemplates(string $wabaId, ?WhatsAppAccount $account = null): array
    {
        $version = $this->getApiVersion($account);
        $url = "{$this->baseUrl}/{$version}/{$wabaId}/message_templates";
        $token = $this->getAccessToken($account);

        $response = Http::withToken($token)
            ->timeout(20)
            ->get($url, [
                'limit' => 100,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch WhatsApp message templates: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Execute an authenticated POST request to Meta Graph API with comprehensive logging.
     */
    protected function executePost(
        WhatsAppAccount $account,
        string $endpoint,
        array $payload,
        ?WhatsAppMessage $messageModel = null
    ): array {
        $version = $this->getApiVersion($account);
        $url = "{$this->baseUrl}/{$version}" . (str_starts_with($endpoint, '/') ? $endpoint : "/{$endpoint}");
        $token = $this->getAccessToken($account);

        $startTime = microtime(true);
        $httpStatus = null;
        $responseBody = null;
        $errorMessage = null;

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post($url, $payload);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->status();
            $responseBody = $response->json() ?? ['raw' => $response->body()];

            if (!$response->successful()) {
                $metaError = $responseBody['error']['message'] ?? $response->body();
                $errorCode = $responseBody['error']['code'] ?? $httpStatus;
                $errorMessage = "Meta WhatsApp API Error [{$errorCode}]: {$metaError}";
                throw new Exception($errorMessage);
            }

            return $responseBody;
        } catch (Exception $e) {
            if (!$errorMessage) {
                $errorMessage = $e->getMessage();
            }
            throw $e;
        } finally {
            $durationMs = isset($durationMs) ? $durationMs : (int) round((microtime(true) - $startTime) * 1000);

            // Log securely without access token
            WhatsAppMessageLog::create([
                'company_id' => $account->company_id,
                'whatsapp_message_id' => $messageModel?->id,
                'api_endpoint' => $endpoint,
                'http_method' => 'POST',
                'http_status' => $httpStatus,
                'request_payload' => $payload,
                'response_payload' => $responseBody,
                'error_details' => $errorMessage,
                'duration_ms' => $durationMs,
            ]);
        }
    }
}
