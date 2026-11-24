<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Lead;
use App\Models\MetaCapiSetting;

class MetaCapiService
{
    protected string $apiVersion = 'v19.0';
    protected string $baseUrl = 'https://graph.facebook.com';

    public function isConfigured(): bool
    {
        return !empty(config('services.meta.app_id')) 
            && !empty(config('services.meta.app_secret'));
    }

    public function sendEvent(array $payload, MetaCapiSetting $settings): array
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$settings->pixel_id}/events";

        $postData = [
            'data' => [$payload],
            'access_token' => $settings->access_token,
        ];

        $testCode = $this->getTestEventCode($settings);
        if ($testCode) {
            $postData['test_event_code'] = $testCode;
        }

        $response = Http::post($url, $postData);

        return [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'json' => $response->json(),
        ];
    }

    public function buildPayload(Lead $lead, string $eventName, string $eventId): array
    {
        $userData = $this->hashUserData($lead);
        
        $payload = [
            'event_name' => $eventName,
            'event_time' => now()->timestamp,
            'action_source' => 'system_generated',
            'event_id' => $eventId,
            'user_data' => $userData,
        ];

        return $payload;
    }

    protected function hashUserData(Lead $lead): array
    {
        $data = [];
        
        if ($lead->email) {
            $data['em'] = $this->hash(strtolower(trim($lead->email)));
        }
        
        if ($lead->phone) {
            // Remove non-numeric characters
            $phone = preg_replace('/[^0-9]/', '', $lead->phone);
            if ($phone) {
                $data['ph'] = $this->hash($phone);
            }
        }

        if ($lead->name) {
            $parts = explode(' ', trim($lead->name), 2);
            $data['fn'] = $this->hash(strtolower(trim($parts[0])));
            if (isset($parts[1])) {
                $data['ln'] = $this->hash(strtolower(trim($parts[1])));
            }
        }

        return $data;
    }

    protected function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    protected function getTestEventCode(MetaCapiSetting $settings): ?string
    {
        if (app()->environment(['local', 'staging']) && $settings->test_event_code) {
            return $settings->test_event_code;
        }
        
        return null;
    }
}
