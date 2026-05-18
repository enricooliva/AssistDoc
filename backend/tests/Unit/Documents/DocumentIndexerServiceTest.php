<?php

namespace Tests\Unit\Documents;

use App\Models\ChunkPreparationRun;
use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\RetrievalModelProfile;
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

    #[Test]
    public function it_stamps_profile_metadata_when_building_profile_aware_segments(): void
    {
        $document = new Document([
            'id' => 12,
            'tenant_id' => 1,
            'filename' => 'manuale.txt',
        ]);
        $profile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $chunkingProfile = ChunkingProfile::query()->where('slug', 'large')->firstOrFail();
        $run = new ChunkPreparationRun();
        $run->forceFill(['id' => 99]);

        $service = app(DocumentIndexerService::class);
        $segments = $service->buildSegments($document, 'Uno due tre quattro cinque sei sette otto nove dieci', $profile, $chunkingProfile, $run);

        $this->assertNotEmpty($segments);
        $this->assertSame((string) $run->id, (string) $segments[0]['chunk_preparation_run_id']);
        $this->assertSame((string) $profile->id, (string) $segments[0]['retrieval_model_profile_id']);
        $this->assertSame((string) $chunkingProfile->id, (string) $segments[0]['chunking_profile_id']);
        $this->assertSame(10, $segments[0]['token_count']);
    }
}
