<?php

namespace App\Services;

class WebhookSignatureService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = config('services.giftflow.webhook_secret', 'default-secret');
    }

    public function verify(string $payload, string $signature): bool
    {
        $expectedSignature = $this->sign($payload);
        
        return hash_equals($expectedSignature, $signature);
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}
