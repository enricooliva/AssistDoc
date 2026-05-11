<?php

namespace App\DataTransferObjects\Chat;

class ChatExchangeData
{
    public function __construct(
        public readonly string $conversationId,
        public readonly ChatMessageData $userMessage,
        public readonly ChatMessageData $assistantMessage,
    ) {
    }

    public function toArray(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'userMessage' => $this->userMessage->toArray(),
            'assistantMessage' => $this->assistantMessage->toArray(),
        ];
    }
}
