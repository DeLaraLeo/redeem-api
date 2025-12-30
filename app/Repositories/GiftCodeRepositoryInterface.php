<?php

namespace App\Repositories;

use App\Domain\GiftCode;
use App\Enums\GiftCodeStatus;

interface GiftCodeRepositoryInterface
{
    public function findByCode(string $code): ?GiftCode;

    public function updateStatus(string $code, GiftCodeStatus $status): void;

    public function save(GiftCode $giftCode): void;

    public function all(): array;

    public function clear(): void;
}
