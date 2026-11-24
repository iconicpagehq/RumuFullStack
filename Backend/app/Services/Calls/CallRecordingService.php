<?php

namespace App\Services\Calls;

use App\Models\CallLog;
use App\Services\Telephony\DownloadedRecording;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CallRecordingService
{
    private const EXTENSIONS = ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'amr', 'aac', '3gp', 'webm'];

    private const MIME_BY_EXT = [
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'm4a' => 'audio/mp4',
        'mp4' => 'video/mp4',
        'ogg' => 'audio/ogg',
        'amr' => 'audio/amr',
        'aac' => 'audio/aac',
        '3gp' => 'video/3gpp',
        'webm' => 'audio/webm',
    ];

    public function validateUpload(UploadedFile $file): array
    {
        if ($file->getSize() > $this->maxBytes()) {
            throw ValidationException::withMessages([
                'recording' => ['The recording is larger than the allowed upload size.'],
            ]);
        }

        $extension = $this->detectExtension($file->getRealPath(), $file->getClientOriginalExtension());

        if (!$extension) {
            throw ValidationException::withMessages([
                'recording' => ['Upload a valid call recording (MP3, WAV, M4A, OGG, AMR, AAC, 3GP or WebM).'],
            ]);
        }

        return [
            'extension' => $extension,
            'mime' => self::MIME_BY_EXT[$extension],
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    public function storeUploaded(CallLog $call, UploadedFile $file, string $extension): void
    {
        $path = $file->storeAs(
            $this->directory($call),
            $this->filename($call, $extension),
            ['disk' => $this->disk()]
        );

        if (!$path) {
            throw ValidationException::withMessages([
                'recording' => ['The recording could not be saved. Please try again.'],
            ]);
        }

        $this->persistMetadata($call, $path, $this->disk());
    }

    public function storeDownloaded(CallLog $call, DownloadedRecording $recording): void
    {
        if (strlen($recording->contents) > $this->maxBytes()) {
            throw ValidationException::withMessages([
                'recording' => ['The provider recording is larger than the allowed size.'],
            ]);
        }

        $extension = $this->detectExtensionFromContents($recording->contents, $recording->extendedExtension);

        if (!$extension) {
            throw ValidationException::withMessages([
                'recording' => ['The provider returned an unsupported recording format.'],
            ]);
        }

        $path = $this->directory($call) . '/' . $this->filename($call, $extension);
        $stored = Storage::disk($this->disk())->put($path, $recording->contents);

        if ($stored === false) {
            throw ValidationException::withMessages([
                'recording' => ['The recording could not be saved.'],
            ]);
        }

        $this->persistMetadata($call, $path, $this->disk(), $recording->mime);
    }

    public function absolutePath(CallLog $call): ?string
    {
        if (!$call->recording_path || !$call->recording_disk) {
            return null;
        }

        $disk = Storage::disk($call->recording_disk);

        return $disk->exists($call->recording_path)
            ? $disk->path($call->recording_path)
            : null;
    }

    public function delete(CallLog $call): void
    {
        if ($call->recording_path && $call->recording_disk) {
            Storage::disk($call->recording_disk)->delete($call->recording_path);
        }

        $call->forceFill([
            'recording_status' => CallLog::RECORDING_EXPIRED,
            'recording_deleted_at' => now(),
            'recording_path' => null,
            'recording_disk' => null,
        ])->save();
    }

    public function disk(): string
    {
        return (string) config('calls.recordings.disk', 'local');
    }

    public function maxBytes(): int
    {
        return (int) config('calls.recordings.max_bytes', 52428800);
    }

    public function retentionDays(): int
    {
        return (int) config('calls.recordings.retention_days', 90);
    }

    private function persistMetadata(CallLog $call, string $path, string $disk, ?string $mime = null): void
    {
        $absolute = Storage::disk($disk)->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $call->forceFill([
            'recording_status' => CallLog::RECORDING_AVAILABLE,
            'recording_disk' => $disk,
            'recording_path' => $path,
            'recording_mime' => $mime ?: (self::MIME_BY_EXT[$extension] ?? 'audio/mpeg'),
            'recording_size' => Storage::disk($disk)->size($path),
            'recording_sha256' => hash_file('sha256', $absolute),
            'recording_duration_seconds' => $call->recording_duration_seconds ?: $call->duration_seconds,
            'recording_deleted_at' => null,
        ])->save();
    }

    private function directory(CallLog $call): string
    {
        return 'call-recordings/' . $call->edition . '/' . now()->format('Y/m');
    }

    private function filename(CallLog $call, string $extension): string
    {
        return $call->uuid . '.' . $extension;
    }

    private function detectExtension(string $path, ?string $clientExtension = null): ?string
    {
        $head = (string) @file_get_contents($path, false, null, 0, 16);

        return $this->detectExtensionFromContents($head, $clientExtension);
    }

    private function detectExtensionFromContents(string $head, ?string $hint = null): ?string
    {
        $hint = strtolower((string) $hint);

        if (str_starts_with($head, 'ID3') || (strlen($head) > 1 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0)) {
            return $hint === 'aac' ? 'aac' : 'mp3';
        }

        if (str_starts_with($head, 'RIFF')) {
            return 'wav';
        }

        if (strlen($head) >= 8 && substr($head, 4, 4) === 'ftyp') {
            return in_array($hint, ['m4a', 'mp4', '3gp'], true) ? $hint : 'm4a';
        }

        if (str_starts_with($head, 'OggS')) {
            return 'ogg';
        }

        if (str_starts_with($head, '#!AMR')) {
            return 'amr';
        }

        if (str_starts_with($head, "\x1A\x45\xDF\xA3")) {
            return 'webm';
        }

        return null;
    }
}
