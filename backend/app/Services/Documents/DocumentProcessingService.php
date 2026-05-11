<?php

namespace App\Services\Documents;

use App\Repositories\DocumentRepository;
use App\Repositories\DocumentSegmentRepository;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Storage;

class DocumentProcessingService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentSegmentRepository $segmentRepository,
        private readonly DocumentIndexerService $documentIndexerService,
        private readonly DocumentService $documentService,
        private readonly PdfTextExtractorService $pdfTextExtractorService,
    ) {
    }

    public function process(string $tenantId, string $documentId): array
    {
        $document = $this->documentRepository->findModel($tenantId, $documentId);
        if (! $document) {
            return [
                'documentId' => $documentId,
                'status' => 'not_found',
            ];
        }

        $this->documentService->markStatus($document, 'processing');
        $this->segmentRepository->deleteForDocument($document);
        $this->documentIndexerService->deleteDocumentVectors($document);

        try {
            $content = $this->extractDocumentText($document->storage_path, $document->media_type);
            if (trim($content) === '') {
                throw new \RuntimeException('Il documento non contiene testo estraibile.');
            }

            $segments = $this->documentIndexerService->buildSegments($document, $content);
            if ($segments === []) {
                throw new \RuntimeException('Non sono stati generati segmenti ricercabili dal documento caricato.');
            }

            $persisted = $this->segmentRepository->replaceForDocument($document, $segments);
            $indexedCount = $this->documentIndexerService->indexSegments($document, $persisted);
            if ($indexedCount !== count($persisted)) {
                throw new \RuntimeException('Non tutti i segmenti hanno ricevuto un embedding valido.');
            }

            $this->segmentRepository->markSearchable($document);
            $document = $this->documentService->markStatus($document, 'ready');
            $this->auditService->record('document.indexed', $tenantId, null, ['document_id' => $documentId, 'segments' => count($persisted)]);
        } catch (\Throwable $exception) {
            $document = $this->documentService->markStatus($document, 'failed', $exception->getMessage());
            $this->auditService->record('document.index_failed', $tenantId, null, [
                'document_id' => $documentId,
                'reason' => $exception->getMessage(),
            ], 'failure');
        }

        return [
            'documentId' => $documentId,
            'status' => $document->status,
        ];
    }

    private function extractDocumentText(string $storagePath, string $mediaType): string
    {
        $contents = Storage::get($storagePath);

        return match ($mediaType) {
            'text/plain', 'text/markdown' => $contents,
            'application/pdf' => $this->pdfTextExtractorService->extract($contents),
            default => $contents,
        };
    }
}
