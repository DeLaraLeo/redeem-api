<?php

namespace App\Domain;

class Redemption
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $code,
        public readonly string $email,
        public readonly string $redeemedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'code' => $this->code,
            'email' => $this->email,
            'redeemed_at' => $this->redeemedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventId: $data['event_id'],
            code: $data['code'],
            email: $data['email'],
            redeemedAt: $data['redeemed_at'],
        );
    }
}
