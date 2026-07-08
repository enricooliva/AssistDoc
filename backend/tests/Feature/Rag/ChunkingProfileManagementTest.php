<?php

namespace Tests\Feature\Rag;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChunkingProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function operator_can_create_and_update_chunking_profiles(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $created = $this->withToken($token)
            ->postJson('/api/v1/rag/chunking-profiles', [
                'name' => 'Profilo Breve',
                'chunkSizeTokens' => 220,
                'overlapTokens' => 20,
            ])
            ->assertCreated()
            ->json();

        $this->withToken($token)
            ->patchJson('/api/v1/rag/chunking-profiles/'.$created['id'], [
                'active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('active', false);
    }
}
