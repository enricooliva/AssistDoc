<?php

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Support\Collection;

class DocumentRepository
{
    public function listForTenant(string $tenantId): array
    {
        return Document::query()
            ->with(['uploader', 'segments', 'activeRetrievalModelProfile', 'activeChunkingProfile', 'activePreparationRun'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('uploaded_at')
            ->get()
            ->map(fn (Document $document) => $this->mapDocument($document))
            ->all();
    }

    public function find(string $tenantId, string $documentId): ?array
    {
        $document = Document::query()
            ->with(['uploader', 'segments', 'activeRetrievalModelProfile', 'activeChunkingProfile', 'activePreparationRun'])
            ->where('tenant_id', $tenantId)
            ->whereKey($documentId)
            ->first();

        return $document ? $this->mapDocument($document) : null;
    }

    public function findModel(string $tenantId, string $documentId): ?Document
    {
        return Document::query()
            ->with(['uploader', 'segments', 'activeRetrievalModelProfile', 'activeChunkingProfile', 'activePreparationRun'])
            ->where('tenant_id', $tenantId)
            ->whereKey($documentId)
            ->first();
    }

    public function create(array $attributes): Document
    {
        return Document::query()->create($attributes);
    }

    public function save(Document $document): Document
    {
        $document->save();

        return $document->refresh(['uploader', 'segments', 'activeRetrievalModelProfile', 'activeChunkingProfile', 'activePreparationRun']);
    }

    private function mapDocument(Document $document): array
    {
        /** @var Collection<int, \App\Models\DocumentSegment> $segments */
        $segments = $document->relationLoaded('segments') ? $document->segments : collect();

        return [
            'id' => (string) $document->id,
            'filename' => $document->filename,
            'mediaType' => $document->media_type,
            'sizeBytes' => $document->size_bytes,
            'status' => $document->status,
            'uploadedAt' => $document->uploaded_at?->toIso8601String(),
            'lastStatusAt' => $document->last_status_at?->toIso8601String(),
            'indexedAt' => $document->indexed_at?->toIso8601String(),
            'failureReason' => $document->failure_reason,
            'tenantId' => (string) $document->tenant_id,
            'uploadedBy' => [
                'id' => $document->uploader ? (string) $document->uploader->id : '',
                'fullName' => $document->uploader?->name ?? 'Utente non disponibile',
            ],
            'activeRetrievalModelProfile' => $document->activeRetrievalModelProfile ? [
                'id' => (string) $document->activeRetrievalModelProfile->id,
                'name' => $document->activeRetrievalModelProfile->name,
            ] : null,
            'activeChunkingProfile' => $document->activeChunkingProfile ? [
                'id' => (string) $document->activeChunkingProfile->id,
                'name' => $document->activeChunkingProfile->name,
            ] : null,
            'activePreparationRunId' => $document->activePreparationRun ? (string) $document->activePreparationRun->id : null,
            'segmentsCount' => $segments->count(),
            'searchableSegmentsCount' => $segments->where('searchable', true)->count(),
        ];
    }
}
