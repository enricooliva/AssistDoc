<?php

namespace App\Jobs;

use App\Repositories\DocumentRepository;
use App\Services\Documents\DocumentIndexerService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteDocumentVectorsJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $documentId,
    ) {
    }

    public function handle(DocumentRepository $documentRepository, DocumentIndexerService $documentIndexerService): void
    {
        $document = $documentRepository->findModelIncludingDeleted($this->tenantId, $this->documentId);

        if (! $document) {
            return;
        }

        $documentIndexerService->deleteDocumentVectors($document);
    }
}
