<?php

namespace App\Repositories;

class ChatConversationRepository
{
    public function listForUser(string $tenantId, string $userId): array
    {
        return [[
            'id' => 'conv-001',
            'title' => 'Panoramica AssistDoc',
            'status' => 'active',
            'lastMessageAt' => now()->toIso8601String(),
            'tenantId' => $tenantId,
            'userId' => $userId,
        ]];
    }

    public function create(string $tenantId, string $userId, ?string $title = null): array
    {
        return [
            'id' => 'conv-'.substr(md5($tenantId.$userId.microtime(true)), 0, 8),
            'title' => $title ?: 'Nuova conversazione',
            'status' => 'active',
            'lastMessageAt' => now()->toIso8601String(),
        ];
    }
}

