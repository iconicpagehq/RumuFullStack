<?php

namespace App\Services;

class MetaCapiSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $skipped = false,
        public readonly ?string $skipReason = null,
        public readonly ?string $metaEventId = null,
        public readonly ?string $errorMessage = null,
        public readonly ?int $logId = null,
    ) {
    }

    public static function skipped(string $reason): self
    {
        return new self(success: false, skipped: true, skipReason: $reason);
    }

    public static function sent(?string $metaEventId, ?int $logId = null): self
    {
        return new self(success: true, metaEventId: $metaEventId, logId: $logId);
    }

    public static function failed(string $errorMessage, ?int $logId = null): self
    {
        return new self(success: false, errorMessage: $errorMessage, logId: $logId);
    }
}
