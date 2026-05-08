<?php

namespace App\Repositories;

use App\Models\ChatConversation;

class ChatConversationRepository
{
    public function listForUser(string $tenantId, string $userId): array
    {
        return ChatConversation::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->latest('last_message_at')
            ->get()
            ->map(fn (ChatConversation $conversation) => [
                'id' => (string) $conversation->id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
                'tenantId' => (string) $conversation->tenant_id,
                'userId' => (string) $conversation->user_id,
            ])->all();
    }

    public function create(string $tenantId, string $userId, ?string $title = null): array
    {
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'title' => $title ?: 'Nuova conversazione',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        return [
            'id' => (string) $conversation->id,
            'title' => $conversation->title,
            'status' => $conversation->status,
            'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
        ];
    }
}
