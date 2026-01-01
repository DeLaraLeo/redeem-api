<?php

use App\DataTransferObjects\RedeemResponse;
use App\Domain\GiftCode;
use App\Domain\Redemption;
use App\Enums\GiftCodeStatus;
use App\Exceptions\GiftCodeAlreadyRedeemedException;
use App\Exceptions\GiftCodeNotFoundException;
use App\Jobs\SendWebhookJob;
use App\Repositories\GiftCodeRepositoryInterface;
use App\Repositories\RedemptionRepositoryInterface;
use App\Services\EventIdGenerator;
use App\Services\RedeemService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class);

describe('RedeemService', function () {
    beforeEach(function () {
        Queue::fake();
        Cache::flush();

        $this->giftCodeRepository = Mockery::mock(GiftCodeRepositoryInterface::class);
        $this->redemptionRepository = Mockery::mock(RedemptionRepositoryInterface::class);
        $this->eventIdGenerator = new EventIdGenerator();

        $this->redeemService = new RedeemService(
            $this->giftCodeRepository,
            $this->redemptionRepository,
            $this->eventIdGenerator,
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('when code does not exist', function () {
        it('throws GiftCodeNotFoundException', function () {
            $this->giftCodeRepository
                ->shouldReceive('findByCode')
                ->with('INVALID-CODE')
                ->andReturn(null);

            expect(fn() => $this->redeemService->redeem('INVALID-CODE', 'user@test.com'))
                ->toThrow(GiftCodeNotFoundException::class);
        });
    });

    describe('when code is already redeemed', function () {
        it('throws GiftCodeAlreadyRedeemedException', function () {
            $alreadyRedeemedGiftCode = new GiftCode(
                code: 'USED-CODE',
                status: GiftCodeStatus::Redeemed,
                productId: 'prod_123',
                creatorId: 'creator_456',
            );

            $this->giftCodeRepository
                ->shouldReceive('findByCode')
                ->with('USED-CODE')
                ->andReturn($alreadyRedeemedGiftCode);

            $this->redemptionRepository
                ->shouldReceive('findByEventId')
                ->andReturn(null);

            expect(fn() => $this->redeemService->redeem('USED-CODE', 'user@test.com'))
                ->toThrow(GiftCodeAlreadyRedeemedException::class);
        });
    });

    describe('when request is idempotent', function () {
        it('returns same response without processing again', function () {
            $availableGiftCode = new GiftCode(
                code: 'VALID-CODE',
                status: GiftCodeStatus::Available,
                productId: 'prod_123',
                creatorId: 'creator_456',
            );

            $expectedEventId = $this->eventIdGenerator->generate('VALID-CODE', 'user@test.com');

            $existingRedemption = new Redemption(
                eventId: $expectedEventId,
                code: 'VALID-CODE',
                email: 'user@test.com',
                redeemedAt: '2024-01-01T00:00:00Z',
            );

            $this->giftCodeRepository
                ->shouldReceive('findByCode')
                ->with('VALID-CODE')
                ->andReturn($availableGiftCode);

            $this->redemptionRepository
                ->shouldReceive('findByEventId')
                ->with($expectedEventId)
                ->andReturn($existingRedemption);

            $this->giftCodeRepository->shouldNotReceive('updateStatus');
            $this->redemptionRepository->shouldNotReceive('create');

            $response = $this->redeemService->redeem('VALID-CODE', 'user@test.com');

            expect($response)->toBeInstanceOf(RedeemResponse::class);
            expect($response->code)->toBe('VALID-CODE');

            Queue::assertNothingPushed();
        });
    });

    describe('successful redemption', function () {
        it('updates status, creates redemption, and dispatches webhook', function () {
            $availableGiftCode = new GiftCode(
                code: 'FRESH-CODE',
                status: GiftCodeStatus::Available,
                productId: 'prod_abc',
                creatorId: 'creator_xyz',
            );

            $this->giftCodeRepository
                ->shouldReceive('findByCode')
                ->with('FRESH-CODE')
                ->andReturn($availableGiftCode);

            $expectedEventId = $this->eventIdGenerator->generate('FRESH-CODE', 'buyer@test.com');

            $this->redemptionRepository
                ->shouldReceive('findByEventId')
                ->with($expectedEventId)
                ->andReturn(null);

            $this->giftCodeRepository
                ->shouldReceive('updateStatus')
                ->with('FRESH-CODE', GiftCodeStatus::Redeemed)
                ->once();

            $this->redemptionRepository
                ->shouldReceive('create')
                ->withArgs(function ($eventId, $code, $email, $timestamp) use ($expectedEventId) {
                    return $eventId === $expectedEventId
                        && $code === 'FRESH-CODE'
                        && $email === 'buyer@test.com';
                })
                ->once();

            $response = $this->redeemService->redeem('FRESH-CODE', 'buyer@test.com');

            expect($response)->toBeInstanceOf(RedeemResponse::class);
            expect($response->status)->toBe('redeemed');
            expect($response->code)->toBe('FRESH-CODE');
            expect($response->creatorId)->toBe('creator_xyz');
            expect($response->productId)->toBe('prod_abc');
            expect($response->eventId)->toBe($expectedEventId);

            Queue::assertPushed(SendWebhookJob::class, function ($job) {
                return $job->code === 'FRESH-CODE'
                    && $job->email === 'buyer@test.com';
            });
        });
    });

    describe('response structure', function () {
        it('returns properly formatted RedeemResponse', function () {
            $giftCode = new GiftCode(
                code: 'STRUCT-TEST',
                status: GiftCodeStatus::Available,
                productId: 'product_structure',
                creatorId: 'creator_structure',
            );

            $this->giftCodeRepository
                ->shouldReceive('findByCode')
                ->andReturn($giftCode);

            $this->redemptionRepository
                ->shouldReceive('findByEventId')
                ->andReturn(null);

            $this->giftCodeRepository->shouldReceive('updateStatus');
            $this->redemptionRepository->shouldReceive('create');

            $response = $this->redeemService->redeem('STRUCT-TEST', 'test@test.com');

            $responseArray = $response->toArray();

            expect($responseArray)->toHaveKeys(['status', 'code', 'creator_id', 'product_id', 'webhook']);
            expect($responseArray['webhook'])->toHaveKeys(['status', 'event_id']);
            expect($responseArray['webhook']['status'])->toBe('queued');
        });
    });
});
