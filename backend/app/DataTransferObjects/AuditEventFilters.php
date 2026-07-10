<?php

namespace App\DataTransferObjects;

class AuditEventFilters
{
    public function __construct(
        public readonly ?string $actor = null,
        public readonly ?string $eventType = null,
        public readonly ?string $outcome = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            actor: $payload['actor'] ?? null,
            eventType: $payload['eventType'] ?? null,
            outcome: $payload['outcome'] ?? null,
            from: $payload['from'] ?? null,
            to: $payload['to'] ?? null,
        );
    }
}

