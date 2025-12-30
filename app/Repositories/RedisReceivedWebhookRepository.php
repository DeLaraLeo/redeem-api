<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class RedisReceivedWebhookRepository implements ReceivedWebhookRepositoryInterface
{
    private const SET_KEY = 'received_webhooks';

    public function hasReceived(string $eventId): bool
    {
        return (bool) Redis::sismember(self::SET_KEY, $eventId);
    }

    public function markAsReceived(string $eventId): void
    {
        Redis::sadd(self::SET_KEY, $eventId);
    }

    public function clear(): void
    {
        Redis::del(self::SET_KEY);
    }
}
