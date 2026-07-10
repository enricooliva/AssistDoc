<?php

namespace Tests\Unit;

use App\Services\QdrantService;
use App\Services\AI\EmbeddingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QdrantServiceTest extends TestCase
{
    #[Test]
    public function it_resets_the_configured_collection_by_deleting_and_recreating_it(): void
    {
        Http::fake(function (Request $request) {
            return Http::response(['result' => 'ok'], 200);
        });

        $service = app(QdrantService::class);
        $result = $service->reset();

        $expectedVectorSize = (int) config('rag.default_retrieval_profile.embedding_dimensions');
        $collection = $result['collection'];

        $this->assertSame(
            (string) config('services.qdrant.collection').'_' . (string) config('rag.default_retrieval_profile.embedding_dimensions'),
            $collection
        );
        $this->assertSame($expectedVectorSize, $result['vector_size']);
        $recorded = Http::recorded();

        $this->assertCount(5, $recorded);
        $this->assertSame(1, collect($recorded)->filter(fn (array $pair): bool => $pair[0]->method() === 'DELETE')->count());
        $this->assertSame(1, collect($recorded)->filter(fn (array $pair): bool => $pair[0]->method() === 'PUT')->count());
        $this->assertSame(3, collect($recorded)->filter(fn (array $pair): bool => $pair[0]->method() === 'POST')->count());
    }

    #[Test]
    public function it_clears_all_points_without_recreating_the_collection(): void
    {
        $collection = (string) config('services.qdrant.collection').'_' . (string) config('rag.default_retrieval_profile.embedding_dimensions');
        $baseUrl = rtrim((string) config('services.qdrant.url'), '/');

        Http::fake([
            "{$baseUrl}/collections/{$collection}" => Http::response(['result' => 'ok'], 200),
            "{$baseUrl}/collections/{$collection}/points/scroll" => Http::sequence()
                ->push([
                    'result' => [
                        'points' => [
                            ['id' => 11],
                            ['id' => 12],
                        ],
                        'next_page_offset' => 12,
                    ],
                ], 200)
                ->push([
                    'result' => [
                        'points' => [],
                        'next_page_offset' => null,
                    ],
                ], 200),
            "{$baseUrl}/collections/{$collection}/points/delete" => Http::response(['result' => ['status' => 'acknowledged']], 200),
        ]);

        $result = app(QdrantService::class)->clear();

        $this->assertSame($collection, $result['collection']);
        $this->assertSame(2, $result['deleted']);
        $this->assertSame(1, $result['chunks']);
        $this->assertCount(4, Http::recorded());
    }
}
