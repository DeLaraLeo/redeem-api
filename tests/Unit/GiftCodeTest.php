<?php

use App\Domain\GiftCode;
use App\Enums\GiftCodeStatus;

describe('GiftCode', function () {
    describe('isRedeemed', function () {
        it('returns true when status is redeemed', function () {
            $giftCode = new GiftCode(
                code: 'TEST-001',
                status: GiftCodeStatus::Redeemed,
                productId: 'prod_123',
                creatorId: 'creator_456',
            );

            expect($giftCode->isRedeemed())->toBeTrue();
        });

        it('returns false when status is available', function () {
            $giftCode = new GiftCode(
                code: 'TEST-001',
                status: GiftCodeStatus::Available,
                productId: 'prod_123',
                creatorId: 'creator_456',
            );

            expect($giftCode->isRedeemed())->toBeFalse();
        });
    });

    describe('markAsRedeemed', function () {
        it('changes status from available to redeemed', function () {
            $giftCode = new GiftCode(
                code: 'TEST-001',
                status: GiftCodeStatus::Available,
                productId: 'prod_123',
                creatorId: 'creator_456',
            );

            expect($giftCode->isRedeemed())->toBeFalse();

            $giftCode->markAsRedeemed();

            expect($giftCode->isRedeemed())->toBeTrue();
            expect($giftCode->status)->toBe(GiftCodeStatus::Redeemed);
        });
    });

    describe('serialization', function () {
        it('serializes to array correctly', function () {
            $giftCode = new GiftCode(
                code: 'GFLOW-001',
                status: GiftCodeStatus::Available,
                productId: 'product_abc',
                creatorId: 'creator_xyz',
            );

            $array = $giftCode->toArray();

            expect($array)->toBe([
                'code' => 'GFLOW-001',
                'status' => 'available',
                'product_id' => 'product_abc',
                'creator_id' => 'creator_xyz',
            ]);
        });

        it('deserializes from array correctly', function () {
            $data = [
                'code' => 'GFLOW-002',
                'status' => 'redeemed',
                'product_id' => 'product_def',
                'creator_id' => 'creator_123',
            ];

            $giftCode = GiftCode::fromArray($data);

            expect($giftCode->code)->toBe('GFLOW-002');
            expect($giftCode->status)->toBe(GiftCodeStatus::Redeemed);
            expect($giftCode->productId)->toBe('product_def');
            expect($giftCode->creatorId)->toBe('creator_123');
        });

        it('maintains data integrity through serialization round-trip', function () {
            $original = new GiftCode(
                code: 'ROUND-TRIP',
                status: GiftCodeStatus::Available,
                productId: 'prod_rt',
                creatorId: 'creator_rt',
            );

            $restored = GiftCode::fromArray($original->toArray());

            expect($restored->code)->toBe($original->code);
            expect($restored->status)->toBe($original->status);
            expect($restored->productId)->toBe($original->productId);
            expect($restored->creatorId)->toBe($original->creatorId);
        });
    });
});
