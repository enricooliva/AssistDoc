<?php

namespace Tests\Unit\AI;

use App\Services\AI\EmbeddingService;
use App\Models\RetrievalModelProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_uses_the_configured_embedding_model_and_dimensions(): void
    {
        $service = app(EmbeddingService::class);

        $vector = $service->embed('AssistDoc usa Qwen per i vettori documentali.');

        $this->assertSame('qwen3-embedding', $service->getEmbeddingModel());
        $this->assertSame('http://192.168.5.137:11434/api/embed', $service->getEmbeddingEndpoint());
        $this->assertSame(4096, $service->getEmbeddingDimensions());
        $this->assertSame(320000, $service->getEmbeddingMaxInputChars());
        $this->assertCount(4096, $vector);
    }

    #[Test]
    public function it_truncates_long_embedding_input_before_generating_the_vector(): void
    {
        $service = app(EmbeddingService::class);

        $vector = $service->embed(str_repeat('abc ', 2000));

        $this->assertCount(4096, $vector);
    }

    #[Test]
    public function it_uses_profile_specific_embedding_dimensions_for_qwen(): void
    {
        $service = app(EmbeddingService::class);
        $profile = RetrievalModelProfile::query()->where('slug', config('rag.profiles.default.slug'))->firstOrFail();

        $vector = $service->embed('Qwen usa embedding da 4096 dimensioni.', $profile);

        $this->assertSame('qwen3-embedding', $service->getEmbeddingModel($profile));
        $this->assertSame(4096, $service->getEmbeddingDimensions($profile));
        $this->assertSame(320000, $service->getEmbeddingMaxInputChars($profile));
        $this->assertCount(4096, $vector);
    }
}
