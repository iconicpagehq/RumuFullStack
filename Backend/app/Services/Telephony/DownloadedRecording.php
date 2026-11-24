<?php

namespace App\Services\Telephony;

class DownloadedRecording
{
    public function __construct(
        public readonly string $contents,
        public readonly string $mime,
        public readonly ?string $extendedExtension = null,
    ) {
    }
}
