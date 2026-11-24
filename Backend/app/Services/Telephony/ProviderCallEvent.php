<?php

namespace App\Services\Telephony;

class ProviderCallEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly ?string $providerCallId,
        public readonly string $eventType,
        public readonly string $status,
        public readonly int $durationSeconds = 0,
        public readonly ?string $recordingUrl = null,
        public readonly ?string $recordingMime = null,
        public readonly ?string $recordingDurationSeconds = null,
        public readonly ?string $startedAt = null,
        public readonly ?string $answeredAt = null,
        public readonly ?string $endedAt = null,
        public readonly array $payload = [],
    ) {
    }
}
