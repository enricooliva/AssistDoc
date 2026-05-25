<?php

namespace Tests\Feature\Rag;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelProfileListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function runtime_retrieval_profile_listing_is_not_exposed_and_qwen_is_configured(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/rag/model-profiles')
            ->assertNotFound();

        $this->assertSame('default', config('rag.profile_preset'));
        $this->assertSame('qwen', config('rag.profiles.default.slug'));
        $this->assertSame('Qwen', config('rag.profiles.default.name'));
        $this->assertSame('qwen-pc-lenti', config('rag.profiles.low_spec.slug'));
        $this->assertSame('qwen3:1.7b', config('rag.profiles.low_spec.generation_model'));
        $this->assertSame('qwen3-embedding:0.6b', config('rag.profiles.low_spec.embedding_model'));
        $this->assertSame(1024, config('rag.profiles.low_spec.embedding_dimensions'));
    }
}
