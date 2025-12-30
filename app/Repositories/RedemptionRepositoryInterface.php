<?php

namespace App\Repositories;

use App\Domain\Redemption;

interface RedemptionRepositoryInterface
{
    public function findByEventId(string $eventId): ?Redemption;

    public function create(string $eventId, string $code, string $email, string $redeemedAt): Redemption;

    public function all(): array;

    public function clear(): void;
}
