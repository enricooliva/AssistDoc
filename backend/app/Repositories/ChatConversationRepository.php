<?php

namespace App\Repositories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatConversationRepository
{
    public function listForUser(string $tenantId, string $userId): array
    {
        return ChatConversation::query()
            ->with(['deletedBy'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->latest('last_message_at')
            ->get()
            ->map(fn (ChatConversation $conversation): array => $this->mapConversation($conversation))
            ->all();
    }

    public function paginateForUser(string $tenantId, string $userId, int $page = 1, int $perPage = 25): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = ChatConversation::query()
            ->with(['deletedBy'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->latest('last_message_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => collect($paginator->items())
                ->map(fn (ChatConversation $conversation): array => $this->mapConversation($conversation))
                ->all(),
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function countForUser(string $tenantId, string $userId): int
    {
        return ChatConversation::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
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
            ->with(['deletedBy'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereKey($conversationId)
            ->first();
    }

    public function findForUserIncludingDeleted(string $tenantId, string $userId, string $conversationId): ?ChatConversation
    {
        return ChatConversation::query()
            ->with(['deletedBy'])
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereKey($conversationId)
            ->first();
    }

    public function save(ChatConversation $conversation): ChatConversation
    {
        $conversation->save();

        return $conversation->refresh();
    }

    public function getThreadForUser(string $tenantId, string $userId, string $conversationId): ?ChatConversation
    {
        return ChatConversation::query()
            ->with([
                'deletedBy',
                'messages' => fn ($query) => $query->with(['citations.document', 'citations.documentSegment.retrievalModelProfile'])->orderBy('created_at'),
            ])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereKey($conversationId)
            ->first();
    }

    public function softDelete(ChatConversation $conversation, string $deletedByUserId): ChatConversation
    {
        $conversation->forceFill([
            'deleted_by_user_id' => $deletedByUserId,
            'deleted_at' => now(),
        ])->save();

        return $conversation->refresh();
    }

    public function createMessage(
        ChatConversation $conversation,
        string $actorType,
        string $body,
        ?string $responseState = null,
        ?string $generationModel = null
    ): ChatMessage {
        $message = $conversation->messages()->create([
            'tenant_id' => $conversation->tenant_id,
            'actor_type' => $actorType,
            'body' => $body,
            'response_state' => $responseState,
            'generation_model' => $generationModel,
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
            'deletedAt' => $conversation->deleted_at?->toIso8601String(),
        ];
    }
}
