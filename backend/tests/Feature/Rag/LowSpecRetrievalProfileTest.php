<?php

namespace Tests\Feature\Rag;

use App\Models\RetrievalModelProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LowSpecRetrievalProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_the_low_spec_retrieval_profile(): void
    {
        config()->set('rag.profile_preset', 'low_spec');
        $this->seed(DatabaseSeeder::class);

        $profile = RetrievalModelProfile::query()->where('slug', config('rag.profiles.low_spec.slug'))->firstOrFail();

        $this->assertSame('Qwen PC Lenti', $profile->name);
        $this->assertSame('qwen3:1.7b', $profile->generation_model);
        $this->assertSame('qwen3-embedding:0.6b', $profile->embedding_model);
        $this->assertSame(32000, $profile->token_window);
        $this->assertSame(1024, $profile->embedding_dimensions);
        $this->assertTrue($profile->available_for_new_runs);
    }
}
