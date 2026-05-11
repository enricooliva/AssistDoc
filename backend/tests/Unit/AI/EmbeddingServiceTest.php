<?php

namespace Tests\Unit\AI;

use App\Services\AI\EmbeddingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    #[Test]
    public function it_uses_the_configured_embedding_model_and_dimensions(): void
    {
        $service = app(EmbeddingService::class);

        $vector = $service->embed('AssistDoc usa mxbai-embed-large per i vettori documentali.');

        $this->assertSame('mxbai-embed-large', $service->getEmbeddingModel());
        $this->assertSame('http://192.168.5.137:11434/api/embeddings', $service->getEmbeddingEndpoint());
        $this->assertSame(1024, $service->getEmbeddingDimensions());
        $this->assertSame(1800, $service->getEmbeddingMaxInputChars());
        $this->assertCount(1024, $vector);
    }

    #[Test]
    public function it_truncates_long_embedding_input_before_generating_the_vector(): void
    {
        $service = app(EmbeddingService::class);

        $vector = $service->embed(str_repeat('abc ', 2000));

        $this->assertCount(1024, $vector);
    }
}
