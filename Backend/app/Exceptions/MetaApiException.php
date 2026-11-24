<?php

namespace App\Exceptions;

use Exception;

class MetaApiException extends Exception
{
    protected ?int $metaErrorCode;
    protected ?int $metaErrorSubcode;
    protected ?string $metaErrorType;
    protected int $httpStatusCode;

    public function __construct(string $message, int $httpStatusCode = 500, ?int $metaErrorCode = null, ?int $metaErrorSubcode = null, ?string $metaErrorType = null)
    {
        parent::__construct($message);
        $this->httpStatusCode = $httpStatusCode;
        $this->metaErrorCode = $metaErrorCode;
        $this->metaErrorSubcode = $metaErrorSubcode;
        $this->metaErrorType = $metaErrorType;
    }

    public function isTransient(): bool
    {
        // Network errors or 5xx
        if ($this->httpStatusCode >= 500) {
            return true;
        }

        // Rate limiting (429)
        if ($this->httpStatusCode === 429) {
            return true;
        }
        
        // Graph API specific rate limit errors
        if ($this->metaErrorCode === 4 || $this->metaErrorCode === 17 || $this->metaErrorCode === 32 || $this->metaErrorCode === 613) {
            return true;
        }

        // Transient timeout/unknown errors
        if ($this->metaErrorCode === 1 || $this->metaErrorCode === 2) {
            return true;
        }

        return false;
    }

    public function isOAuthError(): bool
    {
        return $this->metaErrorType === 'OAuthException' && in_array($this->metaErrorCode, [102, 190, 467]);
    }

    public function getMetaErrorCode(): ?int
    {
        return $this->metaErrorCode;
    }

    public function getMetaErrorSubcode(): ?int
    {
        return $this->metaErrorSubcode;
    }
}
