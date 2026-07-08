<?php

namespace Tests\Feature\Documents;

use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\RetrievalModelProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentPreparationRunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function operator_can_start_a_profile_aware_preparation_run(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $document = $this->withToken($token)
            ->post('/api/v1/documents', [
                'file' => UploadedFile::fake()->createWithContent('manuale.txt', 'AssistDoc supporta il modello Qwen per la ricerca semantica.'),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json();

        $profile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $chunking = ChunkingProfile::query()->where('slug', 'large')->firstOrFail();

        $response = $this->withToken($token)
            ->postJson('/api/v1/documents/'.$document['id'].'/preparation-runs', [
                'chunkingProfileId' => (string) $chunking->id,
            ])
            ->assertAccepted();

        $runId = (string) $response->json('preparationRunId');

        $this->withToken($token)
            ->getJson('/api/v1/documents/'.$document['id'].'/preparation-runs/'.$runId)
            ->assertOk()
            ->assertJsonPath('retrievalModelProfileId', (string) $profile->id)
            ->assertJsonPath('chunkingProfileId', (string) $chunking->id);

        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $document['tenant_id'],
            'event_type' => 'document.indexed',
        ]);
    }

    #[Test]
    public function oversized_chunk_profiles_fail_before_indexing_is_published(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $document = $this->withToken($token)
            ->post('/api/v1/documents', [
                'file' => UploadedFile::fake()->createWithContent('manuale.txt', str_repeat('qwen ', 1000)),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json();

        $invalid = ChunkingProfile::query()->create([
            'name' => 'Troppo Grande',
            'slug' => 'troppo-grande',
            'chunk_size_tokens' => 10000,
            'overlap_tokens' => 10,
            'active' => true,
        ]);

        $response = $this->withToken($token)
            ->postJson('/api/v1/documents/'.$document['id'].'/preparation-runs', [
                'chunkingProfileId' => (string) $invalid->id,
            ])
            ->assertAccepted();

        $runId = (string) $response->json('preparationRunId');

        $this->withToken($token)
            ->getJson('/api/v1/documents/'.$document['id'].'/preparation-runs/'.$runId)
            ->assertOk()
            ->assertJsonPath('status', 'failed');

        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $document['tenant_id'],
            'event_type' => 'document.index_failed',
        ]);
    }
}
