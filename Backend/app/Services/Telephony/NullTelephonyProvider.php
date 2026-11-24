<?php

namespace App\Services\Telephony;

use App\Models\CallLog;
use Illuminate\Http\Request;

class NullTelephonyProvider implements TelephonyProvider
{
    public function name(): string
    {
        return 'disabled';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function initiate(CallLog $call, string $agentNumber): ProviderCallResult
    {
        throw new TelephonyUnavailableException(
            'Cloud calling is not configured yet. Add the calling number manually or import the phone recording.'
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): ProviderCallEvent
    {
        throw new TelephonyUnavailableException('Cloud calling is not configured.');
    }

    public function downloadRecording(CallLog $call): DownloadedRecording
    {
        throw new TelephonyUnavailableException('Recording download is not configured.');
    }

    public function fetchStatus(CallLog $call): ?ProviderCallEvent
    {
        return null;
    }
}
