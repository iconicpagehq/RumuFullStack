<?php

namespace App\Http\Controllers\Api\Calls;

use App\Http\Controllers\Api\ApiController;
use App\Models\CallLog;
use App\Models\CallWebhookEvent;
use App\Services\Calls\CallService;
use App\Services\Telephony\TelephonyManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelephonyWebhookController extends ApiController
{
    public function __invoke(Request $request, string $provider, TelephonyManager $manager)
    {
        if ($provider !== $manager->providerName()) {
            abort(404);
        }

        $adapter = $manager->provider($provider);

        if (!$adapter->isConfigured()) {
            return $this->error('Telephony is not configured.', 503);
        }

        if (!$adapter->verifyWebhook($request)) {
            return $this->error('Invalid webhook signature.', 403);
        }

        try {
            $event = $adapter->parseWebhook($request);
        } catch (\Throwable $exception) {
            Log::warning('Telephony webhook could not be parsed.', [
                'provider' => $provider,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Invalid webhook payload.', 422);
        }

        try {
            $record = CallWebhookEvent::create([
                'provider' => $provider,
                'event_id' => $event->eventId,
                'provider_call_id' => $event->providerCallId,
                'event_type' => $event->eventType,
                'status' => 'received',
                'payload' => $event->payload,
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicate($exception)) {
                return $this->success(['duplicate' => true], 'Webhook already processed.');
            }

            throw $exception;
        }

        $call = $this->resolveCall($event->providerCallId, $event->payload);

        if (!$call) {
            $record->update([
                'status' => 'orphan',
                'error' => 'No matching call log was found.',
            ]);

            return $this->success(['matched' => false], 'Webhook recorded.');
        }

        try {
            app(CallService::class)->applyEvent($call, $event);
        } catch (\Throwable $exception) {
            $record->update([
                'call_log_id' => $call->id,
                'status' => 'failed',
                'error' => substr($exception->getMessage(), 0, 1000),
            ]);

            Log::error('Telephony webhook processing failed.', [
                'call_id' => $call->id,
                'provider' => $provider,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Webhook could not be applied.', 500);
        }

        $record->update([
            'call_log_id' => $call->id,
            'status' => 'processed',
        ]);

        return $this->success(['matched' => true, 'call_id' => $call->id]);
    }

    private function resolveCall(?string $providerCallId, array $payload): ?CallLog
    {
        if ($providerCallId) {
            $call = CallLog::where('provider_call_id', $providerCallId)->first();

            if ($call) {
                return $call;
            }
        }

        $uuid = data_get($payload, 'Call.CustomField')
            ?? data_get($payload, 'CustomField')
            ?? data_get($payload, 'Call.custom_field')
            ?? data_get($payload, 'custom_field');

        return $uuid ? CallLog::where('uuid', $uuid)->first() : null;
    }

    private function isDuplicate(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
