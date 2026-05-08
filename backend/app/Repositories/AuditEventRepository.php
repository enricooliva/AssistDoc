<?php

namespace App\Repositories;

use App\Models\AuditEvent;

class AuditEventRepository
{
    public function record(array $event): array
    {
        return AuditEvent::query()->create($event)->toArray();
    }

    public function query(string $tenantId, array $filters = []): array
    {
        $query = AuditEvent::query()->where('tenant_id', $tenantId);

        if ($filters['eventType'] ?? null) {
            $query->where('event_type', $filters['eventType']);
        }

        if ($filters['outcome'] ?? null) {
            $query->where('outcome', $filters['outcome']);
        }

        return $query->latest('occurred_at')->get()->map(fn (AuditEvent $event) => [
            'id' => (string) $event->id,
            'actor' => $event->actor?->name,
            'eventType' => $event->event_type,
            'targetType' => $event->target_type,
            'targetId' => $event->target_id ? (string) $event->target_id : null,
            'outcome' => $event->outcome,
            'occurredAt' => $event->occurred_at?->toIso8601String(),
            'tenantId' => (string) $event->tenant_id,
        ])->all();
    }
}
