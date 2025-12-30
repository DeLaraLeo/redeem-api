<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

abstract class BaseRedisRepository
{
    abstract protected function getCachePrefix(): string;
    abstract protected function getIndexKey(): string;

    protected function get(string $key): ?array
    {
        return Cache::get($this->getCachePrefix() . $key);
    }

    protected function put(string $key, array $data): void
    {
        Cache::forever($this->getCachePrefix() . $key, $data);
    }

    protected function forget(string $key): void
    {
        Cache::forget($this->getCachePrefix() . $key);
    }

    protected function getIndex(): array
    {
        return Redis::smembers($this->getIndexKey()) ?: [];
    }

    protected function addToIndex(string $key): void
    {
        Redis::sadd($this->getIndexKey(), $key);
    }

    protected function removeFromIndex(string $key): void
    {
        Redis::srem($this->getIndexKey(), $key);
    }

    public function clear(): void
    {
        foreach ($this->getIndex() as $key) {
            $this->forget($key);
        }

        Redis::del($this->getIndexKey());
    }
}
