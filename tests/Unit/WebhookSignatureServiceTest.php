<?php

use App\Services\WebhookSignatureService;
use Tests\TestCase;

uses(TestCase::class);

describe('WebhookSignatureService', function () {
    beforeEach(function () {
        config(['services.giftflow.webhook_secret' => 'test-secret-key']);
        $this->signatureService = new WebhookSignatureService();
    });

    describe('sign', function () {
        it('generates HMAC-SHA256 signature', function () {
            $payload = '{"event_id":"evt_123","type":"test"}';

            $signature = $this->signatureService->sign($payload);

            expect($signature)->toMatch('/^[a-f0-9]{64}$/');
        });

        it('generates deterministic signature for same payload', function () {
            $payload = '{"data":"test"}';

            $firstSignature = $this->signatureService->sign($payload);
            $secondSignature = $this->signatureService->sign($payload);

            expect($firstSignature)->toBe($secondSignature);
        });

        it('generates different signatures for different payloads', function () {
            $firstSignature = $this->signatureService->sign('{"a":1}');
            $secondSignature = $this->signatureService->sign('{"a":2}');

            expect($firstSignature)->not->toBe($secondSignature);
        });
    });

    describe('verify', function () {
        it('accepts valid signature', function () {
            $payload = '{"event_id":"evt_test"}';
            $validSignature = $this->signatureService->sign($payload);

            $isValid = $this->signatureService->verify($payload, $validSignature);

            expect($isValid)->toBeTrue();
        });

        it('rejects invalid signature', function () {
            $payload = '{"event_id":"evt_test"}';

            $isValid = $this->signatureService->verify($payload, 'invalid-signature');

            expect($isValid)->toBeFalse();
        });

        it('rejects tampered payload', function () {
            $originalPayload = '{"amount":100}';
            $tamperedPayload = '{"amount":9999}';
            $originalSignature = $this->signatureService->sign($originalPayload);

            $isValid = $this->signatureService->verify($tamperedPayload, $originalSignature);

            expect($isValid)->toBeFalse();
        });

        it('uses timing-safe comparison', function () {
            $payload = '{"test":true}';
            $validSignature = $this->signatureService->sign($payload);

            expect($this->signatureService->verify($payload, $validSignature))->toBeTrue();
            expect($this->signatureService->verify($payload, 'wrong'))->toBeFalse();
        });
    });
});
