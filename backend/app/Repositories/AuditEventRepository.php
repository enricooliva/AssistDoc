<?php

namespace App\Repositories;

class AuditEventRepository
{
    public function record(array $event): array
    {
        return $event;
    }

    public function query(string $tenantId, array $filters = []): array
    {
        return [[
            'id' => 'audit-001',
            'actor' => $filters['actor'] ?? 'Admin Demo',
            'eventType' => $filters['eventType'] ?? 'chat.question_submitted',
            'targetType' => 'chat_conversation',
            'targetId' => 'conv-001',
            'outcome' => $filters['outcome'] ?? 'success',
            'occurredAt' => now()->toIso8601String(),
            'tenantId' => $tenantId,
        ]];
    }
}

