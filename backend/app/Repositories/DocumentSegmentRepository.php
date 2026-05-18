<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\DocumentSegment;

class DocumentSegmentRepository
{
    public function replaceForDocument(Document $document, array $segments): array
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->whereNull('retired_at')
            ->delete();

        $created = [];
        foreach ($segments as $segment) {
            $created[] = DocumentSegment::query()->create($segment);
        }

        return $created;
    }

    public function markSearchable(Document $document): void
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->whereNull('retired_at')
            ->update([
                'searchable' => true,
                'activated_at' => now(),
            ]);
    }

    public function deleteForDocument(Document $document): void
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->update([
                'searchable' => false,
                'retired_at' => now(),
            ]);
    }

    public function semanticSearch(string $tenantId, string $query, ?string $profileId = null): array
    {
        $builder = DocumentSegment::query()
            ->with('document')
            ->where('tenant_id', $tenantId)
            ->where('searchable', true)
            ->whereNull('retired_at')
            ->where(function ($builder) use ($query): void {
                $builder->where('content_text', 'like', '%'.$query.'%')
                    ->orWhere('source_label', 'like', '%'.$query.'%');
            });

        if ($profileId !== null) {
            $builder->where('retrieval_model_profile_id', $profileId);
        }

        return $builder
            ->limit(5)
            ->get()
            ->map(function (DocumentSegment $segment) use ($tenantId, $query): array {
                return [
                    'documentId' => (string) $segment->document_id,
                    'documentSegmentId' => (string) $segment->id,
                    'documentName' => $segment->document?->filename ?? 'Documento',
                    'snippet' => mb_substr($segment->content_text, 0, 240),
                    'quoteText' => mb_substr($segment->content_text, 0, 240),
                    'score' => 0.9,
                    'sourceLabel' => $segment->source_label,
                    'retrievalModelProfileId' => $segment->retrieval_model_profile_id ? (string) $segment->retrieval_model_profile_id : null,
                    'tenantId' => $tenantId,
                    'query' => $query,
                ];
            })
            ->all();
    }
}
