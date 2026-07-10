<?php

namespace App\Services\Documents;

use App\Repositories\ChunkPreparationRunRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\DocumentSegmentRepository;
use App\Services\Rag\ChunkingProfileService;
use App\Services\Rag\RetrievalModelProfileService;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Storage;

class DocumentProcessingService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentSegmentRepository $segmentRepository,
        private readonly ChunkPreparationRunRepository $preparationRunRepository,
        private readonly DocumentIndexerService $documentIndexerService,
        private readonly DocumentService $documentService,
        private readonly PdfTextExtractorService $pdfTextExtractorService,
        private readonly RetrievalModelProfileService $retrievalModelProfileService,
        private readonly ChunkingProfileService $chunkingProfileService,
    ) {
    }

    public function process(
        string $tenantId,
        string $documentId,
        ?string $chunkingProfileId = null,
        ?string $userId = null,
        bool $preserveExisting = false,
    ): array
    {
        $document = $this->documentRepository->findModel($tenantId, $documentId);
        if (! $document) {
            return [
                'documentId' => $documentId,
                'status' => 'not_found',
            ];
        }

        $profile = $this->retrievalModelProfileService->defaultAvailable();
        $chunkingProfile = $this->chunkingProfileService->resolve($chunkingProfileId);
        $retrievalModelProfileId = (string) $profile->id;
        $resolvedChunkingProfileId = (string) $chunkingProfile->id;
        $run = $this->preparationRunRepository->create([
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'retrieval_model_profile_id' => $profile->id,
            'chunking_profile_id' => $chunkingProfile->id,
            'requested_by_user_id' => $userId,
            'status' => 'queued',
        ]);

        $this->documentService->markStatus($document, 'processing');
        $run->status = 'processing';
        $run->started_at = now();
        $this->preparationRunRepository->save($run);
        if (! $preserveExisting) {
            $this->segmentRepository->deleteForDocument($document, $retrievalModelProfileId, $resolvedChunkingProfileId);         
        }

        try {
            $content = $this->extractDocumentText($document->storage_path, $document->media_type);
            if (trim($content) === '') {
                throw new \RuntimeException('Il documento non contiene testo estraibile.');
            }

            $segments = $this->documentIndexerService->buildSegments($document, $content, $profile, $chunkingProfile, $run);
            if ($segments === []) {
                throw new \RuntimeException('Non sono stati generati segmenti ricercabili dal documento caricato.');
            }

            $persisted = $this->segmentRepository->replaceForDocument(
                $document,
                $segments,
                $retrievalModelProfileId,
                $resolvedChunkingProfileId,
            );
            $indexedCount = $this->documentIndexerService->indexSegments($document, $persisted, $profile);
            if ($indexedCount !== count($persisted)) {
                throw new \RuntimeException('Non tutti i segmenti hanno ricevuto un embedding valido.');
            }

            $this->segmentRepository->markSearchable($document, $retrievalModelProfileId, $resolvedChunkingProfileId);
            $document->active_retrieval_model_profile_id = $profile->id;
            $document->active_chunking_profile_id = $chunkingProfile->id;
            $document->active_preparation_run_id = $run->id;
            $document = $this->documentService->markStatus($document, 'ready');
            $run->status = 'ready';
            $run->completed_at = now();
            $run->failure_code = null;
            $run->failure_message = null;
            $this->preparationRunRepository->save($run);
            $this->auditService->record('document.indexed', $tenantId, $userId, [
                'document_id' => $documentId,
                'segments' => count($persisted),
                'retrieval_model_profile_id' => (string) $profile->id,
                'chunking_profile_id' => (string) $chunkingProfile->id,
            ]);
        } catch (\Throwable $exception) {
            $document = $this->documentService->markStatus($document, 'failed', $exception->getMessage());
            $run->status = 'failed';
            $run->failure_code = 'PREPARATION_FAILED';
            $run->failure_message = $exception->getMessage();
            $run->completed_at = now();
            $this->preparationRunRepository->save($run);
            $this->preparationRunRepository->createValidationFailure([
                'chunk_preparation_run_id' => $run->id,
                'document_id' => $documentId,
                'failure_code' => 'PREPARATION_FAILED',
                'failure_message' => $exception->getMessage(),
                'created_at' => now(),
            ]);
            $this->auditService->record('document.index_failed', $tenantId, $userId, [
                'document_id' => $documentId,
                'reason' => $exception->getMessage(),
                'retrieval_model_profile_id' => (string) $profile->id,
                'chunking_profile_id' => (string) $chunkingProfile->id,
            ], 'failure');
        }

        return [
            'documentId' => $documentId,
            'status' => $document->status,
            'preparationRunId' => (string) $run->id,
        ];
    }

    public function processWithDefaultProfiles(
        string $tenantId,
        string $documentId,
        ?string $userId = null,
    ): array
    {
        $chunkingProfiles = $this->chunkingProfileService->defaultProfiles();
        $document = $this->documentRepository->findModel($tenantId, $documentId);

        if ($chunkingProfiles === []) {
            throw new \RuntimeException('Nessun profilo di segmentazione disponibile.');
        }

        $lastResult = [
            'documentId' => $documentId,
            'status' => 'not_found',
            'preparationRunId' => null,
        ];

        $this->documentIndexerService->deleteDocumentVectors($document);

        foreach (array_values($chunkingProfiles) as $chunkingProfile) {
            $lastResult = $this->process(
                $tenantId,
                $documentId,
                (string) $chunkingProfile->id,
                $userId,
                false
            );
        }

        return $lastResult;
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
