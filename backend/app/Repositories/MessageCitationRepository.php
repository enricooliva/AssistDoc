<?php

namespace App\Repositories;

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
            MessageCitation::query()->create([
                'tenant_id' => $message->tenant_id,
                'chat_message_id' => $message->id,
                'document_id' => $citation['documentId'],
                'document_segment_id' => $citation['documentSegmentId'],
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
