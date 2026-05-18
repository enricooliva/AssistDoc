<?php

namespace Tests\Unit\AI;

use App\Models\ChunkingProfile;
use App\Models\RetrievalModelProfile;
use App\Services\AI\TokenizerService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TokenizerServiceTest extends TestCase
{
    #[Test]
    public function it_counts_tokens_and_splits_text_with_overlap(): void
    {
        $service = app(TokenizerService::class);
        $profile = new RetrievalModelProfile([
            'token_window' => 100,
            'tokenizer_key' => 'test',
        ]);
        $chunkingProfile = new ChunkingProfile([
            'chunk_size_tokens' => 4,
            'overlap_tokens' => 1,
        ]);

        $this->assertSame(6, $service->countTokens('uno due tre quattro cinque sei'));

        $chunks = $service->splitText('uno due tre quattro cinque sei', $profile, $chunkingProfile);

        $this->assertCount(2, $chunks);
        $this->assertSame(4, $chunks[0]['token_count']);
        $this->assertSame('quattro cinque sei', $chunks[1]['content']);
    }

    #[Test]
    public function it_rejects_invalid_chunking_profiles_before_splitting(): void
    {
        $service = app(TokenizerService::class);
        $profile = new RetrievalModelProfile([
            'token_window' => 100,
            'tokenizer_key' => 'test',
        ]);
        $chunkingProfile = new ChunkingProfile([
            'chunk_size_tokens' => 4,
            'overlap_tokens' => 4,
        ]);

        $this->expectException(\RuntimeException::class);
        $service->splitText('uno due tre quattro cinque sei', $profile, $chunkingProfile);
    }

    #[Test]
    public function it_rejects_chunk_sizes_that_exceed_the_selected_model_window(): void
    {
        $service = app(TokenizerService::class);
        $profile = new RetrievalModelProfile([
            'token_window' => 3,
            'tokenizer_key' => 'test',
        ]);
        $chunkingProfile = new ChunkingProfile([
            'chunk_size_tokens' => 4,
            'overlap_tokens' => 1,
        ]);

        $this->expectException(\RuntimeException::class);
        $service->splitText('uno due tre quattro cinque sei', $profile, $chunkingProfile);
    }
}
