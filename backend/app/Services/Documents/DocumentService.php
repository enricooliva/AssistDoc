<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Repositories\DocumentRepository;
use App\Repositories\DocumentSegmentRepository;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentSegmentRepository $documentSegmentRepository,
        private readonly AuditService $auditService,
        private readonly DocumentIndexerService $documentIndexerService,
    ) {
    }

    public function list(string $tenantId, int $page = 1, int $perPage = 25): array
    {
        return $this->documentRepository->paginateForTenant($tenantId, $page, $perPage);
    }

    public function create(string $tenantId, string $userId, array $payload): array
    {
        /** @var UploadedFile $file */
        $file = $payload['file'];
        $tags = $this->normalizeTags($payload['tags'] ?? []);
        $storagePath = $file->storeAs(
            sprintf('documents/%s', $tenantId),
            sprintf('%s-%s', now()->timestamp, $file->getClientOriginalName()),
        );

        $document = $this->documentRepository->create([
            'tenant_id' => $tenantId,
            'uploaded_by_user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'media_type' => $file->getMimeType() ?: 'application/octet-stream',
            'storage_path' => $storagePath,
            'tags' => $tags,
            'size_bytes' => $file->getSize(),
            'status' => 'queued',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => null,
            'failure_reason' => null,
        ]);

        $this->auditService->record('document.uploaded', $tenantId, $userId, [
            'document_id' => (string) $document->id,
            'filename' => $document->filename,
            'storage_path' => $document->storage_path,
            'tags' => $tags,
        ]);

        app(DocumentProcessingService::class)->processWithDefaultProfiles(
            $tenantId,
            (string) $document->id,
            $userId,
        );

        return $this->show($tenantId, (string) $document->id) ?? [];
    }

    public function show(string $tenantId, string $documentId): ?array
    {
        return $this->documentRepository->find($tenantId, $documentId);
    }

    public function delete(string $tenantId, string $userId, string $documentId): array
    {
        $document = $this->documentRepository->findModelIncludingDeleted($tenantId, $documentId);

        if (! $document) {
            return [
                'status' => 'not_found',
                'documentId' => $documentId,
            ];
        }

        if ($document->deleted_at !== null) {
            return [
                'status' => 'already_deleted',
                'documentId' => $documentId,
                'deletedAt' => $document->deleted_at?->toIso8601String(),
            ];
        }

        $document = DB::transaction(function () use ($document, $tenantId, $userId): Document {
            $document = $this->documentRepository->softDelete($document, $userId);
            $this->documentSegmentRepository->hardDeleteForDocument($document);
            $this->auditService->record(
                'document.deleted',
                $tenantId,
                $userId,
                [
                    'document_id' => (string) $document->id,
                    'deleted_at' => $document->deleted_at?->toIso8601String(),
                    'deleted_by_user_id' => $userId,
                    'previous_status' => 'active',
                ],
                'success',
                'document',
                $document->id,
            );

            return $document;
        });

        $this->documentIndexerService->deleteDocumentVectors($document);

        return [
            'documentId' => (string) $document->id,
            'status' => 'deleted',
            'deletedAt' => $document->deleted_at?->toIso8601String(),
            'deletedBy' => $document->deleter ? [
                'id' => (string) $document->deleter->id,
                'fullName' => $document->deleter->name,
            ] : [
                'id' => $userId,
                'fullName' => 'Utente non disponibile',
            ],
            'removedFromList' => true,
        ];
    }

    public function retry(string $tenantId, string $userId, string $documentId): array
    {
        $document = $this->documentRepository->findModel($tenantId, $documentId);
        if (! $document) {
            return [
                'status' => 'not_found',
                'documentId' => $documentId,
            ];
        }

        if ($document->status !== 'failed') {
            return [
                'status' => $document->status,
                'documentId' => $documentId,
            ];
        }

        $document->status = 'queued';
        $document->failure_reason = null;
        $document->last_status_at = now();
        $this->documentRepository->save($document);

        $this->auditService->record('document.retry_requested', $tenantId, $userId, ['document_id' => $documentId]);
        app(DocumentProcessingService::class)->processWithDefaultProfiles(
            $tenantId,
            $documentId,
            $userId,
        );

        return [
            'status' => 'queued',
            'documentId' => $documentId,
        ];
    }

    public function markStatus(Document $document, string $status, ?string $failureReason = null): Document
    {
        $document->status = $status;
        $document->last_status_at = now();
        $document->failure_reason = $failureReason;
        if ($status === 'ready') {
            $document->indexed_at = now();
        }
        if ($status !== 'ready') {
            $document->indexed_at = null;
        }

        return $this->documentRepository->save($document);
    }

    public function startPreparationRun(
        string $tenantId,
        string $userId,
        string $documentId,
        string $chunkingProfileId,
    ): array {
        $document = $this->documentRepository->findModel($tenantId, $documentId);
        if (! $document) {
            return [
                'status' => 'not_found',
                'documentId' => $documentId,
            ];
        }

        return app(DocumentProcessingService::class)->process(
            $tenantId,
            $documentId,
            $chunkingProfileId,
            $userId,
        );
    }

    private function normalizeTags(array $tags): array
    {
        $normalized = [];

        foreach ($tags as $tag) {
            $value = trim((string) $tag);

            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value);
            $normalized[$key] = $value;
        }

        return array_values($normalized);
    }
}
