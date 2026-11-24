<?php

namespace App\Services\Telephony;

use App\Models\CallLog;
use Illuminate\Http\Request;

interface TelephonyProvider
{
    public function name(): string;

    public function isConfigured(): bool;

    public function initiate(CallLog $call, string $agentNumber): ProviderCallResult;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): ProviderCallEvent;

    public function downloadRecording(CallLog $call): DownloadedRecording;

    public function fetchStatus(CallLog $call): ?ProviderCallEvent;
}
