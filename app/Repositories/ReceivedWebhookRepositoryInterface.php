<?php

namespace App\Repositories;

interface ReceivedWebhookRepositoryInterface
{
    public function hasReceived(string $eventId): bool;

    public function markAsReceived(string $eventId): void;

    public function clear(): void;
}
