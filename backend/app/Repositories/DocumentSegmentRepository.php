<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\DocumentSegment;

class DocumentSegmentRepository
{
    public function replaceForDocument(
        Document $document,
        array $segments,
        ?string $retrievalModelProfileId = null,
        ?string $chunkingProfileId = null,
    ): array
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->when($retrievalModelProfileId !== null, fn ($query) => $query->where('retrieval_model_profile_id', $retrievalModelProfileId))
            ->when($chunkingProfileId !== null, fn ($query) => $query->where('chunking_profile_id', $chunkingProfileId))
            ->whereNull('retired_at')
            ->delete();

        $created = [];
        foreach ($segments as $segment) {
            $created[] = DocumentSegment::query()->create($segment);
        }

        return $created;
    }

    public function markSearchable(
        Document $document,
        ?string $retrievalModelProfileId = null,
        ?string $chunkingProfileId = null,
    ): void
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->when($retrievalModelProfileId !== null, fn ($query) => $query->where('retrieval_model_profile_id', $retrievalModelProfileId))
            ->when($chunkingProfileId !== null, fn ($query) => $query->where('chunking_profile_id', $chunkingProfileId))
            ->whereNull('retired_at')
            ->update([
                'searchable' => true,
                'activated_at' => now(),
            ]);
    }

    public function deleteForDocument(
        Document $document,
        ?string $retrievalModelProfileId = null,
        ?string $chunkingProfileId = null,
    ): void
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->when($retrievalModelProfileId !== null, fn ($query) => $query->where('retrieval_model_profile_id', $retrievalModelProfileId))
            ->when($chunkingProfileId !== null, fn ($query) => $query->where('chunking_profile_id', $chunkingProfileId))
            ->update([
                'searchable' => false,
                'retired_at' => now(),
            ]);
    }

    public function hardDeleteForDocument(Document $document): void
    {
        DocumentSegment::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->delete();
    }

    public function semanticSearch(
        string $tenantId,
        string $query,
        ?string $profileId = null,
        array $tags = [],
        ?string $chunkingProfileId = null,
    ): array
    {
        $builder = DocumentSegment::query()
            ->with(['document', 'retrievalModelProfile'])
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

        if ($chunkingProfileId !== null && $chunkingProfileId !== '') {
            $builder->where('chunking_profile_id', $chunkingProfileId);
        }

        if ($tags !== []) {
            $builder->whereHas('document', function ($query) use ($tags): void {
                $query->where(function ($query) use ($tags): void {
                    foreach ($tags as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                });
            });
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
                    'embeddingModel' => $segment->embedding_model ?? $segment->retrievalModelProfile?->embedding_model,
                    'tenantId' => $tenantId,
                    'query' => $query,
                ];
            })
            ->all();
    }
}
