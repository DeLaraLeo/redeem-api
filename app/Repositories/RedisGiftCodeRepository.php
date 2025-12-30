<?php

namespace App\Repositories;

use App\Domain\GiftCode;
use App\Enums\GiftCodeStatus;

class RedisGiftCodeRepository extends BaseRedisRepository implements GiftCodeRepositoryInterface
{
    protected function getCachePrefix(): string
    {
        return 'giftcode:';
    }

    protected function getIndexKey(): string
    {
        return 'giftcodes:index';
    }

    public function findByCode(string $code): ?GiftCode
    {
        $data = $this->get($code);

        return $data ? GiftCode::fromArray($data) : null;
    }

    public function updateStatus(string $code, GiftCodeStatus $status): void
    {
        $data = $this->get($code);

        if ($data) {
            $data['status'] = $status->value;
            $this->put($code, $data);
        }
    }

    public function save(GiftCode $giftCode): void
    {
        $this->put($giftCode->code, $giftCode->toArray());
        $this->addToIndex($giftCode->code);
    }

    public function all(): array
    {
        return array_filter(
            array_map(
                fn(string $code) => $this->findByCode($code),
                $this->getIndex()
            )
        );
    }
}
