<?php

namespace App\Services\Telephony;

use App\Models\CallLog;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExotelTelephonyProvider implements TelephonyProvider
{
    public function name(): string
    {
        return 'exotel';
    }

    public function isConfigured(): bool
    {
        $config = config('telephony.exotel', []);

        return filled($config['subdomain'] ?? null)
            && filled($config['account_sid'] ?? null)
            && filled($config['api_key'] ?? null)
            && filled($config['api_token'] ?? null)
            && filled($config['caller_id'] ?? null);
    }

    public function initiate(CallLog $call, string $agentNumber): ProviderCallResult
    {
        $this->assertConfigured();

        $response = $this->client()
            ->asForm()
            ->post($this->accountUrl('/Calls/connect'), [
                'From' => $this->normalizeNumber($agentNumber),
                'To' => $this->normalizeNumber($call->to_number),
                'CallerId' => $this->config()['caller_id'],
                'TimeLimit' => $this->config()['time_limit'] ?? 14400,
                'Timeout' => $this->config()['timeout'] ?? 45,
                'Record' => ($this->config()['record'] ?? true) ? 'true' : 'false',
                'RecordingChannels' => 'dual',
                'StatusCallback' => $this->config()['status_callback_url'] ?? url('/api/v1/webhooks/telephony/exotel'),
                'StatusCallbackContentType' => 'application/json',
                'CustomField' => $call->uuid,
            ]);

        if ($response->failed()) {
            throw new TelephonyUnavailableException(
                'The calling provider rejected this call. Please check the calling number and try again.'
            );
        }

        $payload = $response->json() ?: [];
        $data = $payload['Call'] ?? $payload;

        return new ProviderCallResult(
            providerCallId: $data['Sid'] ?? $data['sid'] ?? null,
            status: $this->normalizeStatus($data['Status'] ?? $data['status'] ?? 'ringing'),
            payload: $payload,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = config('telephony.webhook.secret');

        if (blank($secret)) {
            return false;
        }

        $header = config('telephony.webhook.signature_header', 'X-Rumu-Signature');
        $signature = (string) $request->header($header, '');
        $signature = Str::after($signature, 'sha256=');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): ProviderCallEvent
    {
        $payload = $request->json()->all() ?: $request->all();
        $data = $payload['Call'] ?? $payload;
        $recordingUrl = $this->first($data, ['RecordingUrl', 'recording_url', 'RecordingURL']);

        $providerCallId = $this->first($data, ['CallSid', 'call_sid', 'Sid', 'sid']);
        $status = $this->normalizeStatus($this->first($data, ['Status', 'status', 'CallStatus']) ?? 'ringing');
        $duration = (int) ($this->first($data, ['Duration', 'duration', 'CallDuration']) ?? 0);

        $eventId = $this->first($data, ['EventId', 'event_id']);
        if (blank($eventId)) {
            $eventId = hash('sha256', $providerCallId . '|' . $status . '|' . $recordingUrl . '|' . $duration);
        }

        return new ProviderCallEvent(
            eventId: (string) $eventId,
            providerCallId: $providerCallId ? (string) $providerCallId : null,
            eventType: $recordingUrl ? 'recording.ready' : 'call.status',
            status: $status,
            durationSeconds: $duration,
            recordingUrl: $recordingUrl ? (string) $recordingUrl : null,
            recordingMime: $this->first($data, ['RecordingContentType', 'recording_content_type']) ?: 'audio/mpeg',
            recordingDurationSeconds: $this->first($data, ['RecordingDuration', 'recording_duration']),
            startedAt: $this->first($data, ['StartTime', 'start_time']),
            answeredAt: $this->first($data, ['AnsweredTime', 'answered_time']),
            endedAt: $this->first($data, ['EndTime', 'end_time']),
            payload: $payload,
        );
    }

    public function downloadRecording(CallLog $call): DownloadedRecording
    {
        $this->assertConfigured();

        $url = data_get($call->provider_payload, 'RecordingUrl')
            ?? data_get($call->provider_payload, 'recording_url');

        if (blank($url)) {
            $event = $this->fetchStatus($call);
            $url = $event?->recordingUrl;
        }

        if (blank($url)) {
            throw new TelephonyUnavailableException('The provider has not published a recording for this call yet.');
        }

        $response = $this->withCredentials()->timeout(90)->get($url);

        if ($response->failed()) {
            throw new TelephonyUnavailableException('Could not download the recording from the provider.');
        }

        return new DownloadedRecording(
            contents: $response->body(),
            mime: $response->header('Content-Type') ?: 'audio/mpeg',
            extendedExtension: pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: null,
        );
    }

    public function fetchStatus(CallLog $call): ?ProviderCallEvent
    {
        if (!$this->isConfigured() || blank($call->provider_call_id)) {
            return null;
        }

        $response = $this->client()->get($this->accountUrl('/Calls/' . urlencode($call->provider_call_id) . '.json'));

        if ($response->failed()) {
            return null;
        }

        return $this->eventFromResponse($response, $call->provider_call_id);
    }

    private function eventFromResponse(Response $response, string $providerCallId): ProviderCallEvent
    {
        $payload = $response->json() ?: [];
        $data = $payload['Call'] ?? $payload;
        $recordingUrl = $this->first($data, ['RecordingUrl', 'recording_url']);

        return new ProviderCallEvent(
            eventId: 'status-' . $providerCallId . '-' . md5($response->body()),
            providerCallId: $providerCallId,
            eventType: $recordingUrl ? 'recording.ready' : 'call.status',
            status: $this->normalizeStatus($this->first($data, ['Status', 'status']) ?? 'ringing'),
            durationSeconds: (int) ($this->first($data, ['Duration', 'duration']) ?? 0),
            recordingUrl: $recordingUrl ? (string) $recordingUrl : null,
            recordingMime: $this->first($data, ['RecordingContentType']) ?: 'audio/mpeg',
            recordingDurationSeconds: $this->first($data, ['RecordingDuration']),
            startedAt: $this->first($data, ['StartTime']),
            answeredAt: $this->first($data, ['AnsweredTime']),
            endedAt: $this->first($data, ['EndTime']),
            payload: $payload,
        );
    }

    private function client()
    {
        return $this->withCredentials()->acceptJson();
    }

    private function withCredentials()
    {
        $config = $this->config();

        return Http::withBasicAuth($config['api_key'], $config['api_token']);
    }

    private function accountUrl(string $path): string
    {
        return rtrim($this->baseUrl(), '/')
            . '/v1/Accounts/' . rawurlencode($this->config()['account_sid'])
            . $path;
    }

    private function baseUrl(): string
    {
        $subdomain = trim((string) $this->config()['subdomain']);

        return Str::startsWith($subdomain, ['http://', 'https://'])
            ? $subdomain
            : 'https://' . $subdomain;
    }

    private function config(): array
    {
        return config('telephony.exotel', []);
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new TelephonyUnavailableException(
                'Cloud calling is not configured yet. Add the calling number manually or import the phone recording.'
            );
        }
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'in-progress', 'in_progress', 'answered', 'connected' => CallLog::STATUS_IN_PROGRESS,
            'completed', 'complete', 'ended' => CallLog::STATUS_COMPLETED,
            'no-answer', 'no_answer', 'missed' => CallLog::STATUS_NO_ANSWER,
            'busy', 'failed', 'fail', 'error' => CallLog::STATUS_FAILED,
            'canceled', 'cancelled' => CallLog::STATUS_CANCELED,
            default => CallLog::STATUS_RINGING,
        };
    }

    private function normalizeNumber(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }

        return $digits !== '' ? '+' . ltrim($digits, '+') : '';
    }

    private function first(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && filled($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }
}
