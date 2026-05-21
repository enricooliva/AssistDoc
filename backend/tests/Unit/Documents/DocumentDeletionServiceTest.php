<?php

namespace Tests\Unit\Documents;

use App\Jobs\DeleteDocumentVectorsJob;
use App\Models\Document;
use App\Repositories\DocumentRepository;
use App\Services\Documents\DocumentIndexerService;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentDeletionServiceTest extends TestCase
{
    #[Test]
    public function delete_document_vectors_job_loads_the_document_and_cleans_qdrant_vectors(): void
    {
        $document = new Document([
            'tenant_id' => 'tenant-1',
            'uploaded_by_user_id' => 'user-1',
            'filename' => 'manuale.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/tenant-1/manuale.txt',
            'size_bytes' => 123,
            'status' => 'deleted',
        ]);
        $document->id = '42';

        $repository = $this->createMock(DocumentRepository::class);
        $repository->expects($this->once())
            ->method('findModelIncludingDeleted')
            ->with('tenant-1', '42')
            ->willReturn($document);

        $indexer = $this->createMock(DocumentIndexerService::class);
        $indexer->expects($this->once())
            ->method('deleteDocumentVectors')
            ->with($document);

        $job = new DeleteDocumentVectorsJob('tenant-1', '42');
        $job->handle($repository, $indexer);
    }

    #[Test]
    public function delete_document_vectors_job_is_a_noop_when_the_document_is_missing(): void
    {
        $repository = $this->createMock(DocumentRepository::class);
        $repository->expects($this->once())
            ->method('findModelIncludingDeleted')
            ->with('tenant-1', '42')
            ->willReturn(null);

        $indexer = $this->createMock(DocumentIndexerService::class);
        $indexer->expects($this->never())->method('deleteDocumentVectors');

        $job = new DeleteDocumentVectorsJob('tenant-1', '42');
        $job->handle($repository, $indexer);
    }
}
