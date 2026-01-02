<?php

namespace App\Services;

use App\DataTransferObjects\RedeemResponse;
use App\Domain\GiftCode;
use App\Enums\GiftCodeStatus;
use App\Exceptions\GiftCodeAlreadyRedeemedException;
use App\Exceptions\GiftCodeNotFoundException;
use App\Jobs\SendWebhookJob;
use App\Repositories\GiftCodeRepositoryInterface;
use App\Repositories\RedemptionRepositoryInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class RedeemService
{
    public function __construct(
        private GiftCodeRepositoryInterface $giftCodeRepository,
        private RedemptionRepositoryInterface $redemptionRepository,
        private EventIdGenerator $eventIdGenerator,
    ) {}

    public function redeem(string $code, string $email): RedeemResponse
    {
        $giftCode = $this->findGiftCodeOrFail($code);
        $eventId = $this->eventIdGenerator->generate($code, $email);

        if ($this->isAlreadyRedeemed($eventId)) {
            return $this->createResponse($giftCode, $eventId);
        }

        return $this->executeWithLock($code, function () use ($code, $email, $eventId) {
            $freshGiftCode = $this->findGiftCodeOrFail($code);

            $this->ensureCodeIsAvailable($freshGiftCode);
            $this->processRedemption($code, $eventId, $email, $freshGiftCode);

            return $this->createResponse($freshGiftCode, $eventId);
        });
    }

    private function executeWithLock(string $code, callable $callback): RedeemResponse
    {
        $lock = Cache::lock("redeem:{$code}", config('giftflow.lock.timeout'));

        try {
            return $lock->block(config('giftflow.lock.wait'), $callback);
        } catch (LockTimeoutException) {
            throw new \App\Exceptions\LockAcquisitionException($code);
        }
    }

    private function findGiftCodeOrFail(string $code): GiftCode
    {
        $giftCode = $this->giftCodeRepository->findByCode($code);

        if (!$giftCode) {
            throw new GiftCodeNotFoundException($code);
        }

        return $giftCode;
    }

    private function isAlreadyRedeemed(string $eventId): bool
    {
        return $this->redemptionRepository->findByEventId($eventId) !== null;
    }

    private function ensureCodeIsAvailable(GiftCode $giftCode): void
    {
        if ($giftCode->isRedeemed()) {
            throw new GiftCodeAlreadyRedeemedException($giftCode->code);
        }
    }

    private function processRedemption(string $code, string $eventId, string $email, GiftCode $giftCode): void
    {
        $this->giftCodeRepository->updateStatus($code, GiftCodeStatus::Redeemed);

        $this->redemptionRepository->create(
            $eventId,
            $code,
            $email,
            now()->toIso8601String()
        );

        $this->dispatchWebhook($eventId, $code, $email, $giftCode);
    }

    private function dispatchWebhook(string $eventId, string $code, string $email, GiftCode $giftCode): void
    {
        SendWebhookJob::dispatch(
            $eventId,
            $code,
            $email,
            $giftCode->creatorId,
            $giftCode->productId
        );
    }

    private function createResponse(GiftCode $giftCode, string $eventId): RedeemResponse
    {
        return new RedeemResponse(
            status: 'redeemed',
            code: $giftCode->code,
            creatorId: $giftCode->creatorId,
            productId: $giftCode->productId,
            eventId: $eventId,
        );
    }
}
