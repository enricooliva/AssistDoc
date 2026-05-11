<?php

namespace Tests\Unit\Documents;

use App\Models\Document;
use App\Services\AI\EmbeddingService;
use App\Services\Documents\DocumentIndexerService;
use App\Services\QdrantService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentIndexerServiceTest extends TestCase
{
    #[Test]
    public function it_builds_searchable_segments_from_text(): void
    {
        $document = new Document([
            'id' => 10,
            'tenant_id' => 1,
            'filename' => 'manuale.txt',
        ]);

        $service = app(DocumentIndexerService::class);
        $segments = $service->buildSegments($document, "Prima riga.\n\nSeconda riga con contenuto utile.");

        $this->assertNotEmpty($segments);
        $this->assertSame(0, $segments[0]['segment_index']);
        $this->assertSame('Segmento 1', $segments[0]['source_label']);
    }

    #[Test]
    public function it_indexes_segments_into_the_local_qdrant_service(): void
    {
        $document = new Document();
        $document->forceFill([
            'id' => 11,
            'tenant_id' => 1,
            'filename' => 'manuale.txt',
        ]);

        $service = app(DocumentIndexerService::class);
        $qdrant = app(QdrantService::class);
        $queryVector = app(EmbeddingService::class)->embed('AssistDoc rende il contenuto semanticamente ricercabile.');
        $service->indexSegments($document, [[
            'id' => 44,
            'segment_index' => 0,
            'source_label' => 'Segmento 1',
            'content_text' => 'AssistDoc rende il contenuto semanticamente ricercabile.',
        ]]);

        $results = $qdrant->search('documents', $queryVector, [
            'must' => [
                ['key' => 'document_id', 'match' => ['value' => '11']],
            ],
        ]);

        $this->assertCount(1, $results);
    }
}
