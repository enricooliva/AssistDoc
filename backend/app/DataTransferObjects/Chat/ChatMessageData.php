<?php

namespace App\DataTransferObjects\Chat;

use App\Models\ChatMessage;
use App\Models\MessageCitation;

class ChatMessageData
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorType,
        public readonly string $body,
        public readonly ?string $responseState,
        public readonly ?string $generationModel,
        public readonly ?array $citations,
        public readonly ?string $createdAt,
    ) {
    }

    public static function fromModel(ChatMessage $message): self
    {
        $citations = $message->relationLoaded('citations')
            ? $message->citations->map(fn (MessageCitation $citation): array => [
                'documentId' => (string) $citation->document_id,
                'documentName' => $citation->document?->filename ?? 'Documento',
                'sourceLabel' => $citation->source_label,
                'quoteText' => $citation->quote_text,
                'embedding' => $citation->documentSegment?->embedding_model,
                'collection' => (string) config('services.qdrant.collection', 'assistdoc_segments').'_' . (
                    $citation->documentSegment?->retrievalModelProfile?->embedding_dimensions
                    ?? (int) config('rag.default_retrieval_profile.embedding_dimensions', 4096)
                ),
            ])->all()
            : null;

        return new self(
            id: (string) $message->id,
            actorType: $message->actor_type,
            body: $message->body,
            responseState: $message->response_state,
            generationModel: $message->generation_model,
            citations: $citations,
            createdAt: $message->created_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'actorType' => $this->actorType,
            'body' => $this->body,
            'responseState' => $this->responseState,
            'generationModel' => $this->generationModel,
            'citations' => $this->citations,
            'createdAt' => $this->createdAt,
        ];
    }
}
