<?php

namespace App\Services\Telephony;

class ProviderCallResult
{
    public function __construct(
        public readonly ?string $providerCallId,
        public readonly string $status,
        public readonly array $payload = [],
    ) {
    }
}
