<?php

namespace App\Services\Audit;

use App\Repositories\AuditEventRepository;

class AuditService
{
    public function __construct(private readonly AuditEventRepository $repository)
    {
    }

    public function record(string $eventType, string $tenantId, ?string $actorUserId, array $metadata = [], string $outcome = 'success'): array
    {
        return $this->repository->record([
            'id' => 'audit-'.substr(md5($eventType.$tenantId.microtime(true)), 0, 8),
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'metadata' => $metadata,
            'outcome' => $outcome,
            'occurred_at' => now()->toIso8601String(),
        ]);
    }
}

