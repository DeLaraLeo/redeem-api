<?php

use App\DataTransferObjects\RedeemResponse;

describe('RedeemResponse', function () {
    it('formats response with correct structure', function () {
        $response = new RedeemResponse(
            status: 'redeemed',
            code: 'GFLOW-TEST-0001',
            creatorId: 'creator_123',
            productId: 'product_abc',
            eventId: 'evt_abc123def456',
        );

        $array = $response->toArray();

        expect($array)->toBe([
            'status' => 'redeemed',
            'code' => 'GFLOW-TEST-0001',
            'creator_id' => 'creator_123',
            'product_id' => 'product_abc',
            'webhook' => [
                'status' => 'queued',
                'event_id' => 'evt_abc123def456',
            ],
        ]);
    });

    it('always sets webhook status as queued', function () {
        $response = new RedeemResponse(
            status: 'redeemed',
            code: 'CODE',
            creatorId: 'creator',
            productId: 'product',
            eventId: 'evt_test',
        );

        $array = $response->toArray();

        expect($array['webhook']['status'])->toBe('queued');
    });

    it('preserves all properties as readonly', function () {
        $response = new RedeemResponse(
            status: 'redeemed',
            code: 'GFLOW-001',
            creatorId: 'creator_id',
            productId: 'product_id',
            eventId: 'evt_readonly',
        );

        expect($response->status)->toBe('redeemed');
        expect($response->code)->toBe('GFLOW-001');
        expect($response->creatorId)->toBe('creator_id');
        expect($response->productId)->toBe('product_id');
        expect($response->eventId)->toBe('evt_readonly');
    });
});
