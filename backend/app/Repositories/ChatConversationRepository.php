<?php

namespace App\Repositories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;

class ChatConversationRepository
{
    public function listForUser(string $tenantId, string $userId): array
    {
        return ChatConversation::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->latest('last_message_at')
            ->get()
            ->map(fn (ChatConversation $conversation): array => $this->mapConversation($conversation))
            ->all();
    }

    public function countForUser(string $tenantId, string $userId): int
    {
        return ChatConversation::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->count();
    }

    public function create(string $tenantId, string $userId, ?string $title = null): ChatConversation
    {
        return ChatConversation::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'title' => $title ?: 'Nuova conversazione',
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    public function findForUser(string $tenantId, string $userId, string $conversationId): ?ChatConversation
    {
        return ChatConversation::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereKey($conversationId)
            ->first();
    }

    public function getThreadForUser(string $tenantId, string $userId, string $conversationId): ?ChatConversation
    {
        return ChatConversation::query()
            ->with([
                'messages' => fn ($query) => $query->with(['citations.document'])->orderBy('created_at'),
            ])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereKey($conversationId)
            ->first();
    }

    public function createMessage(
        ChatConversation $conversation,
        string $actorType,
        string $body,
        ?string $responseState = null
    ): ChatMessage {
        $message = $conversation->messages()->create([
            'tenant_id' => $conversation->tenant_id,
            'actor_type' => $actorType,
            'body' => $body,
            'response_state' => $responseState,
            'created_at' => now(),
        ]);

        $this->touchLastMessage($conversation, $message->created_at ?? now());

        return $message;
    }

    public function touchLastMessage(ChatConversation $conversation, \DateTimeInterface $time): ChatConversation
    {
        $conversation->last_message_at = $time;
        $conversation->save();

        return $conversation->refresh();
    }

    public function mapConversation(ChatConversation $conversation): array
    {
        return [
            'id' => (string) $conversation->id,
            'title' => $conversation->title,
            'status' => $conversation->status,
            'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
        ];
    }
}
