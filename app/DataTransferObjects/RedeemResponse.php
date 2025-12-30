<?php

namespace App\DataTransferObjects;

class RedeemResponse
{
    public function __construct(
        public readonly string $status,
        public readonly string $code,
        public readonly string $creatorId,
        public readonly string $productId,
        public readonly string $eventId,
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'code' => $this->code,
            'creator_id' => $this->creatorId,
            'product_id' => $this->productId,
            'webhook' => [
                'status' => 'queued',
                'event_id' => $this->eventId,
            ],
        ];
    }
}
