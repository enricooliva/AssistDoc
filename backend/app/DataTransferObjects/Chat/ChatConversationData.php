<?php

namespace App\DataTransferObjects\Chat;

use App\Models\ChatConversation;

class ChatConversationData
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $status,
        public readonly ?string $lastMessageAt,
    ) {
    }

    public static function fromModel(ChatConversation $conversation): self
    {
        return new self(
            id: (string) $conversation->id,
            title: $conversation->title,
            status: $conversation->status,
            lastMessageAt: $conversation->last_message_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'lastMessageAt' => $this->lastMessageAt,
        ];
    }
}
