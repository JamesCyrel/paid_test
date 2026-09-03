<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class BigBlueButtonException extends Exception
{
    protected ?string $returnCode;

    protected ?string $messageKey;

    protected ?string $rawResponse;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $returnCode = null,
        ?string $messageKey = null,
        ?string $rawResponse = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->returnCode = $returnCode;
        $this->messageKey = $messageKey;
        $this->rawResponse = $rawResponse;
    }

    public static function failedResponse(string $messageKey, string $message, ?string $rawResponse = null): self
    {
        return new self(
            message: ! empty($message) ? "BigBlueButton API error ($messageKey): $message" : "BigBlueButton API error: $messageKey",
            returnCode: 'FAILED',
            messageKey: $messageKey,
            rawResponse: $rawResponse
        );
    }

    public static function connectionError(string $message, int $code = 0, ?Throwable $previous = null): self
    {
        return new self(
            message: "Unable to connect to BigBlueButton server: $message",
            code: $code,
            previous: $previous
        );
    }

    public static function invalidResponse(string $message, ?string $rawResponse = null): self
    {
        return new self(
            message: "Invalid response from BigBlueButton server: $message",
            rawResponse: $rawResponse
        );
    }

    public function getReturnCode(): ?string
    {
        return $this->returnCode;
    }

    public function getMessageKey(): ?string
    {
        return $this->messageKey;
    }

    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }
}
