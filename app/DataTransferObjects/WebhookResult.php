<?php

namespace App\DataTransferObjects;

readonly class WebhookResult
{
    private function __construct(
        public bool $success,
        public int $status,
        public string $message,
        public ?string $error = null,
    ) {}

    public static function success(string $message, int $status = 200): self
    {
        return new self(
            success: true,
            status: $status,
            message: $message,
        );
    }

    public static function failure(string $error, string $message, int $status): self
    {
        return new self(
            success: false,
            status: $status,
            message: $message,
            error: $error,
        );
    }
}
