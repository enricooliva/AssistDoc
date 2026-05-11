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
            ->update(['searchable' => true]);
    }

    public function deleteForDocument(Document $document): void
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->delete();
    }

    public function semanticSearch(string $tenantId, string $query): array
    {
        return DocumentSegment::query()
            ->with('document')
            ->where('tenant_id', $tenantId)
            ->where('searchable', true)
            ->where(function ($builder) use ($query): void {
                $builder->where('content_text', 'like', '%'.$query.'%')
                    ->orWhere('source_label', 'like', '%'.$query.'%');
            })
            ->limit(5)
            ->get()
            ->map(function (DocumentSegment $segment) use ($tenantId, $query): array {
                return [
                    'documentId' => (string) $segment->document_id,
                    'documentName' => $segment->document?->filename ?? 'Documento',
                    'snippet' => mb_substr($segment->content_text, 0, 240),
                    'score' => 0.9,
                    'sourceLabel' => $segment->source_label,
                    'tenantId' => $tenantId,
                    'query' => $query,
                ];
            })
            ->all();
    }
}
