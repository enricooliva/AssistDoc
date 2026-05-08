<?php

namespace App\Repositories;

class DocumentRepository
{
    public function listForTenant(string $tenantId): array
    {
        return [[
            'id' => 'doc-001',
            'filename' => 'Manuale Aziendale.pdf',
            'mediaType' => 'application/pdf',
            'sizeBytes' => 124000,
            'status' => 'indexed',
            'uploadedAt' => now()->toIso8601String(),
            'lastStatusAt' => now()->toIso8601String(),
            'indexedAt' => now()->toIso8601String(),
            'failureReason' => null,
            'tenantId' => $tenantId,
        ]];
    }

    public function find(string $tenantId, string $documentId): ?array
    {
        foreach ($this->listForTenant($tenantId) as $document) {
            if ($document['id'] === $documentId) {
                return $document;
            }
        }

        return null;
    }
}

