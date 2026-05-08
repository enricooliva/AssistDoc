<?php

namespace App\Repositories;

use App\Models\Document;

class DocumentRepository
{
    public function listForTenant(string $tenantId): array
    {
        return Document::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('uploaded_at')
            ->get()
            ->map(fn (Document $document) => $this->mapDocument($document))
            ->all();
    }

    public function find(string $tenantId, string $documentId): ?array
    {
        $document = Document::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($documentId)
            ->first();

        return $document ? $this->mapDocument($document) : null;
    }

    private function mapDocument(Document $document): array
    {
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
        ];
    }
}
