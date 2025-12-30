<?php

namespace App\Repositories;

use App\Domain\Redemption;

class RedisRedemptionRepository extends BaseRedisRepository implements RedemptionRepositoryInterface
{
    protected function getCachePrefix(): string
    {
        return 'redemption:';
    }

    protected function getIndexKey(): string
    {
        return 'redemptions:index';
    }

    public function findByEventId(string $eventId): ?Redemption
    {
        $data = $this->get($eventId);
        return $data ? Redemption::fromArray($data) : null;
    }

    public function create(string $eventId, string $code, string $email, string $redeemedAt): Redemption
    {
        $redemption = new Redemption(
            eventId: $eventId,
            code: $code,
            email: $email,
            redeemedAt: $redeemedAt,
        );

        $this->put($eventId, $redemption->toArray());
        $this->addToIndex($eventId);

        return $redemption;
    }

    public function all(): array
    {
        return array_filter(
            array_map(
                fn(string $eventId) => $this->findByEventId($eventId),
                $this->getIndex()
            )
        );
    }
}
