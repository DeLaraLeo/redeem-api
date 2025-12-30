<?php

namespace App\Domain;

use App\Enums\GiftCodeStatus;

class GiftCode
{
    public function __construct(
        public readonly string $code,
        public GiftCodeStatus $status,
        public readonly string $productId,
        public readonly string $creatorId,
    ) {}

    public function isRedeemed(): bool
    {
        return $this->status === GiftCodeStatus::Redeemed;
    }

    public function markAsRedeemed(): void
    {
        $this->status = GiftCodeStatus::Redeemed;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'status' => $this->status->value,
            'product_id' => $this->productId,
            'creator_id' => $this->creatorId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            status: GiftCodeStatus::from($data['status']),
            productId: $data['product_id'],
            creatorId: $data['creator_id'],
        );
    }
}
