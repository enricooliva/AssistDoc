<?php

namespace App\Services\Audit;

use App\Repositories\AuditEventRepository;

class AuditService
{
    public function __construct(private readonly AuditEventRepository $repository)
    {
    }

    public function record(
        string $eventType,
        string $tenantId,
        ?string $actorUserId,
        array $metadata = [],
        string $outcome = 'success',
        ?string $targetType = null,
        int|string|null $targetId = null
    ): array
    {
        return $this->repository->record([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'outcome' => $outcome,
            'occurred_at' => now()->toIso8601String(),
        ]);
    }
}
