<?php

namespace App\Services\Documents;

use App\Services\Audit\AuditService;

class DocumentProcessingService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function process(string $tenantId, string $documentId): array
    {
        $this->auditService->record('document.indexed', $tenantId, null, ['document_id' => $documentId]);

        return [
            'documentId' => $documentId,
            'status' => 'indexed',
        ];
    }
}

