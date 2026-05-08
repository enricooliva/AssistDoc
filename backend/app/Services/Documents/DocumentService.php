<?php

namespace App\Services\Documents;

use App\Repositories\DocumentRepository;
use App\Services\Audit\AuditService;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly AuditService $auditService,
    ) {
    }

    public function list(string $tenantId): array
    {
        return [
            'items' => $this->documentRepository->listForTenant($tenantId),
            'page' => 1,
            'perPage' => 25,
            'total' => 1,
        ];
    }

    public function create(string $tenantId, string $userId, array $payload): array
    {
        $document = [
            'id' => 'doc-'.substr(md5($payload['filename'].microtime(true)), 0, 8),
            'filename' => $payload['filename'],
            'mediaType' => $payload['mediaType'] ?? 'application/pdf',
            'sizeBytes' => (int) ($payload['sizeBytes'] ?? 0),
            'status' => 'queued',
            'uploadedAt' => now()->toIso8601String(),
            'lastStatusAt' => now()->toIso8601String(),
            'indexedAt' => null,
            'failureReason' => null,
        ];

        $this->auditService->record('document.uploaded', $tenantId, $userId, ['document_id' => $document['id']]);

        return $document;
    }

    public function show(string $tenantId, string $documentId): ?array
    {
        return $this->documentRepository->find($tenantId, $documentId);
    }

    public function retry(string $tenantId, string $userId, string $documentId): array
    {
        $this->auditService->record('document.retry_requested', $tenantId, $userId, ['document_id' => $documentId]);

        return [
            'status' => 'queued',
            'documentId' => $documentId,
        ];
    }
}

