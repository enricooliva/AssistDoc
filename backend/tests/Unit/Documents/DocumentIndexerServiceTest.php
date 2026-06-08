<?php

namespace Tests\Unit\Documents;

use App\Models\ChunkPreparationRun;
use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\RetrievalModelProfile;
use App\Services\AI\EmbeddingService;
use App\Services\Documents\DocumentIndexerService;
use App\Services\QdrantService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentIndexerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

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
        $this->assertSame('qwen3-embedding', $segments[0]['embedding_model']);
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

        $capturedPoints = [];
        $this->mock(QdrantService::class, function ($mock) use (&$capturedPoints): void {
            $mock->shouldReceive('generatePointId')
                ->once()
                ->andReturn(12345);
            $mock->shouldReceive('upsert')
                ->once()
                ->with(
                    Mockery::on(function (array $points) use (&$capturedPoints): bool {
                        $capturedPoints = $points;

                        return true;
                    }),
                    Mockery::any(),
                    Mockery::any(),
                    null,
                )
                ->andReturn(['status' => 'ok']);
        });

        $service = app(DocumentIndexerService::class);
        $service->indexSegments($document, [[
            'id' => 44,
            'segment_index' => 0,
            'source_label' => 'Segmento 1',
            'content_text' => 'AssistDoc rende il contenuto semanticamente ricercabile.',
        ]]);

        $this->assertNotEmpty($capturedPoints);
        $this->assertSame('qwen3-embedding', $capturedPoints[0]['payload']['embedding_model']);
        $this->assertSame('11', $capturedPoints[0]['payload']['document_id']);
        $this->assertSame('AssistDoc rende il contenuto semanticamente ricercabile.', $capturedPoints[0]['payload']['content_text']);
    }

    #[Test]
    public function it_keeps_distinct_vector_points_for_different_chunking_profiles(): void
    {
        $document = new Document();
        $document->forceFill([
            'id' => 21,
            'tenant_id' => 1,
            'filename' => 'manuale.txt',
        ]);

        $profile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $smallChunkingProfile = ChunkingProfile::query()->where('slug', 'small')->firstOrFail();
        $largeChunkingProfile = ChunkingProfile::query()->where('slug', 'large')->firstOrFail();
        $pointIds = [];
        $this->mock(QdrantService::class, function ($mock) use (&$pointIds): void {
            $mock->shouldReceive('generatePointId')
                ->twice()
                ->andReturn(11111, 22222);
            $mock->shouldReceive('upsert')
                ->twice()
                ->andReturnUsing(function (array $points) use (&$pointIds): array {
                    $pointIds[] = $points[0]['id'];

                    return ['status' => 'ok'];
                });
        });
        $service = app(DocumentIndexerService::class);

        $service->indexSegments($document, [[
            'id' => 100,
            'segment_index' => 0,
            'source_label' => 'Segmento 1',
            'content_text' => 'Contenuto con profilo small.',
            'retrieval_model_profile_id' => $profile->id,
            'chunking_profile_id' => $smallChunkingProfile->id,
        ]], $profile);
        $service->indexSegments($document, [[
            'id' => 101,
            'segment_index' => 0,
            'source_label' => 'Segmento 1',
            'content_text' => 'Contenuto con profilo large.',
            'retrieval_model_profile_id' => $profile->id,
            'chunking_profile_id' => $largeChunkingProfile->id,
        ]], $profile);

        $this->assertCount(2, $pointIds);
        $this->assertNotSame($pointIds[0], $pointIds[1]);
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
        $this->assertSame($profile->embedding_model, $segments[0]['embedding_model']);
        $this->assertSame((string) $chunkingProfile->id, (string) $segments[0]['chunking_profile_id']);
        $this->assertSame(10, $segments[0]['token_count']);
    }

    #[Test]
    public function it_deletes_vectors_using_the_document_active_retrieval_profile_and_vector_size(): void
    {
        $profile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();

        $document = new Document();
        $document->forceFill([
            'id' => 31,
            'tenant_id' => 1,
            'active_retrieval_model_profile_id' => $profile->id,
        ]);
        $document->setRelation('activeRetrievalModelProfile', $profile);

        $this->mock(QdrantService::class, function ($mock) use ($document, $profile): void {
            $mock->shouldReceive('deleteByFilter')
                ->once()
                ->with(
                    Mockery::on(function (array $filter) use ($document, $profile): bool {
                        $must = $filter['must'] ?? [];

                        return $must[0]['key'] === 'tenant_id'
                            && $must[0]['match']['value'] === (string) $document->tenant_id
                            && $must[1]['key'] === 'document_id'
                            && $must[1]['match']['value'] === (string) $document->id
                            && $must[2]['key'] === 'retrieval_model_profile_id'
                            && $must[2]['match']['value'] === (string) $profile->id;
                    }),
                    null,
                    $profile->embedding_dimensions,
                    $profile->slug,
                );
        });

        $service = app(DocumentIndexerService::class);
        $service->deleteDocumentVectors($document);
    }
}
