<?php

use App\Repositories\GiftCodeRepositoryInterface;
use App\Repositories\RedemptionRepositoryInterface;
use App\Repositories\ReceivedWebhookRepositoryInterface;

const VALID_CODE = 'GFLOW-TEST-0001';
const VALID_CODE_2 = 'GFLOW-TEST-0002';
const USED_CODE = 'GFLOW-USED-0003';
const INVALID_CODE = 'INVALID-CODE-XXX';

beforeEach(function () {
    app(GiftCodeRepositoryInterface::class)->clear();
    app(RedemptionRepositoryInterface::class)->clear();
    app(ReceivedWebhookRepositoryInterface::class)->clear();

    $this->artisan('giftflow:seed');
});

function redeemCode(string $code, string $email = 'test@example.com')
{
    return test()->postJson('/api/redeem', [
        'code' => $code,
        'user' => ['email' => $email],
    ]);
}

it('redeems a valid gift code and persists the state', function () {
    $response = redeemCode(VALID_CODE);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'redeemed')
        ->assertJsonPath('data.code', VALID_CODE)
        ->assertJsonPath('data.webhook.status', 'queued')
        ->assertJsonStructure([
            'data' => [
                'status',
                'code',
                'creator_id',
                'product_id',
                'webhook' => ['status', 'event_id'],
            ],
        ]);

    $giftCode = app(GiftCodeRepositoryInterface::class)->findByCode(VALID_CODE);
    expect($giftCode->isRedeemed())->toBeTrue();

    $eventId = $response->json('data.webhook.event_id');
    $redemption = app(RedemptionRepositoryInterface::class)->findByEventId($eventId);
    expect($redemption)->not->toBeNull();
});

it('returns 404 for non-existent code', function () {
    $response = redeemCode(INVALID_CODE);

    $response->assertStatus(404)
        ->assertJsonPath('error', 'not_found');
});

it('returns 409 for already redeemed code', function () {
    $response = redeemCode(USED_CODE);

    $response->assertStatus(409)
        ->assertJsonPath('error', 'already_redeemed');
});

it('returns 422 for invalid request', function () {
    $response = test()->postJson('/api/redeem', [
        'code' => VALID_CODE,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user.email']);
});

it('is idempotent for same code and email', function () {
    $email = 'idempotent@example.com';

    $firstRequest = redeemCode(VALID_CODE_2, $email);
    $secondRequest = redeemCode(VALID_CODE_2, $email);

    $firstRequest->assertStatus(200);
    $secondRequest->assertStatus(200);

    expect($firstRequest->json('data.webhook.event_id'))
        ->toBe($secondRequest->json('data.webhook.event_id'));

    $giftCode = app(GiftCodeRepositoryInterface::class)->findByCode(VALID_CODE_2);
    expect($giftCode->isRedeemed())->toBeTrue();
});
