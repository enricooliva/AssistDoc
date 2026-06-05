<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\ChatMessage;
use App\Models\MessageCitation;

class MessageCitationRepository
{
    public function replaceForMessage(ChatMessage $message, array $citations): array
    {
        MessageCitation::query()
            ->where('tenant_id', $message->tenant_id)
            ->where('chat_message_id', $message->id)
            ->delete();

        foreach ($citations as $citation) {
            $documentId = (string) ($citation['documentId'] ?? '');
            $documentSegmentId = (string) ($citation['documentSegmentId'] ?? '');

            if ($documentSegmentId === '') {
                continue;
            }

            $segment = DocumentSegment::query()
                ->whereKey($documentSegmentId)
                ->where('tenant_id', $message->tenant_id)
                ->first();

            if (! $segment) {
                continue;
            }

            $documentId = $documentId !== '' ? $documentId : (string) $segment->document_id;

            if ($documentId === '' || (string) $segment->document_id !== $documentId) {
                continue;
            }

            if (! Document::query()
                ->whereKey($documentId)
                ->where('tenant_id', $message->tenant_id)
                ->exists()) {
                continue;
            }

            MessageCitation::query()->create([
                'tenant_id' => $message->tenant_id,
                'chat_message_id' => $message->id,
                'document_id' => $documentId,
                'document_segment_id' => $documentSegmentId,
                'quote_text' => $citation['quoteText'],
                'source_label' => $citation['sourceLabel'],
                'created_at' => now(),
            ]);
        }

        return MessageCitation::query()
            ->with('document')
            ->where('tenant_id', $message->tenant_id)
            ->where('chat_message_id', $message->id)
            ->orderBy('id')
            ->get()
            ->all();
    }
}
