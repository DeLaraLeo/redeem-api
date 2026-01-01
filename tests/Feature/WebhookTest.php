<?php

use App\Repositories\ReceivedWebhookRepositoryInterface;
use App\Services\WebhookSignatureService;

beforeEach(function () {
    app(ReceivedWebhookRepositoryInterface::class)->clear();
});

function sendWebhook(string $payload, string $signature)
{
    return test()->call(
        'POST',
        '/api/webhook/issuer-platform',
        [],
        [],
        [],
        ['HTTP_X_GIFTFLOW_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $payload
    );
}

it('rejects invalid webhook signature', function () {
    $payload = json_encode([
        'event_id' => 'evt_test_123',
        'type' => 'giftcard.redeemed',
        'data' => ['code' => 'TEST'],
    ]);

    $response = sendWebhook($payload, 'invalid-signature');

    expect($response->status())->toBe(401);
    expect($response->json('error'))->toBe('invalid_signature');
});

it('accepts valid webhook and ignores duplicates', function () {
    $signatureService = app(WebhookSignatureService::class);
    $eventId = 'evt_duplicate_test';

    $payload = json_encode([
        'event_id' => $eventId,
        'type' => 'giftcard.redeemed',
        'data' => ['code' => 'TEST'],
    ]);

    $signature = $signatureService->sign($payload);

    $firstWebhook = sendWebhook($payload, $signature);
    $duplicateWebhook = sendWebhook($payload, $signature);

    expect($firstWebhook->status())->toBe(200);
    expect($duplicateWebhook->status())->toBe(200);
    expect($duplicateWebhook->json('message'))->toBe('Webhook already processed.');

    $repository = app(ReceivedWebhookRepositoryInterface::class);
    expect($repository->hasReceived($eventId))->toBeTrue();
});
