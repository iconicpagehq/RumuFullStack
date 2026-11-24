<?php

namespace App\Jobs;

use App\Models\CallLog;
use App\Services\Calls\CallRecordingService;
use App\Services\Telephony\TelephonyProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadCallRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $callId)
    {
        $this->onQueue('calls');
    }

    public function handle(TelephonyProvider $provider, CallRecordingService $recordings): void
    {
        $call = CallLog::find($this->callId);

        if (!$call || $call->hasRecordedAudio()) {
            return;
        }

        try {
            $recording = $provider->downloadRecording($call);
            $recordings->storeDownloaded($call, $recording);
        } catch (\Throwable $exception) {
            Log::warning('Call recording download failed', [
                'call_id' => $call->id,
                'error' => $exception->getMessage(),
            ]);

            $call->forceFill(['recording_status' => CallLog::RECORDING_FAILED])->save();
        }
    }
}
