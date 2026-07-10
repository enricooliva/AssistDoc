<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\MessageCitation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function operator_can_soft_delete_a_document_and_retire_segments(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'file',
            'filename' => 'da-eliminare.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/da-eliminare.txt',
            'tags' => ['da-rimuovere'],
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now()->subHour(),
            'last_status_at' => now()->subHour(),
            'indexed_at' => now()->subHour(),
        ]);

        DocumentSegment::query()->create([
            'tenant_id' => $tenant->id,
            'document_id' => $document->id,
            'chunk_preparation_run_id' => null,
            'retrieval_model_profile_id' => null,
            'chunking_profile_id' => null,
            'segment_index' => 0,
            'content_text' => 'Contenuto da ritirare.',
            'token_count' => 5,
            'source_label' => 'Segmento 1',
            'searchable' => true,
            'activated_at' => now()->subHour(),
            'retired_at' => null,
        ]);

        $this->mock(\App\Services\Documents\DocumentIndexerService::class, function ($mock) use ($document): void {
            $mock->shouldReceive('deleteDocumentVectors')
                ->once()
                ->with(\Mockery::on(fn (Document $deletedDocument): bool => $deletedDocument->id === $document->id));
        });

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/documents/'.$document->id)
            ->assertOk()
            ->assertJsonPath('status', 'deleted')
            ->assertJsonPath('removedFromList', true);

        $this->assertNotNull($response->json('deletedAt'));
        $this->assertSame('Operator Demo', $response->json('deletedBy.fullName'));

        $document->refresh();
        $this->assertNotNull($document->deleted_at);
        $this->assertSame((string) $operator->id, (string) $document->deleted_by_user_id);
        $this->assertSame('deleted', $document->status);

        $this->assertDatabaseMissing('document_segments', [
            'document_id' => $document->id,
        ]);
    }

    #[Test]
    public function deleting_a_document_removes_related_message_citations(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'text',
            'filename' => 'Nota interna',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/nota.txt',
            'tags' => ['da-rimuovere'],
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now()->subHour(),
            'last_status_at' => now()->subHour(),
            'indexed_at' => now()->subHour(),
        ]);

        $segment = DocumentSegment::query()->create([
            'tenant_id' => $tenant->id,
            'document_id' => $document->id,
            'chunk_preparation_run_id' => null,
            'retrieval_model_profile_id' => null,
            'chunking_profile_id' => null,
            'segment_index' => 0,
            'content_text' => 'Contenuto da ritirare.',
            'token_count' => 5,
            'source_label' => 'Segmento 1',
            'searchable' => true,
            'activated_at' => now()->subHour(),
            'retired_at' => null,
        ]);

        $message = \App\Models\ChatMessage::query()->create([
            'tenant_id' => $tenant->id,
            'conversation_id' => \App\Models\ChatConversation::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $operator->id,
                'title' => 'Cleanup',
                'status' => 'active',
                'last_message_at' => now(),
            ])->id,
            'actor_type' => 'assistant',
            'body' => 'Risposta',
            'response_state' => 'answered',
            'created_at' => now(),
        ]);

        MessageCitation::query()->create([
            'tenant_id' => $tenant->id,
            'chat_message_id' => $message->id,
            'document_id' => $document->id,
            'document_segment_id' => $segment->id,
            'quote_text' => 'Contenuto da ritirare.',
            'source_label' => 'Segmento 1',
            'created_at' => now(),
        ]);

        $this->mock(\App\Services\Documents\DocumentIndexerService::class, function ($mock) use ($document): void {
            $mock->shouldReceive('deleteDocumentVectors')->once()->with(\Mockery::on(
                fn (Document $deletedDocument): bool => $deletedDocument->id === $document->id
            ));
        });

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->deleteJson('/api/v1/documents/'.$document->id)
            ->assertOk();

        $this->assertDatabaseMissing('message_citations', [
            'document_id' => $document->id,
        ]);
    }

    #[Test]
    public function deleting_the_same_document_twice_returns_a_conflict(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'file',
            'filename' => 'gia-eliminato.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/gia-eliminato.txt',
            'tags' => [],
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now()->subHour(),
            'last_status_at' => now()->subHour(),
            'indexed_at' => now()->subHour(),
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->deleteJson('/api/v1/documents/'.$document->id)
            ->assertOk();

        $this->withToken($token)
            ->deleteJson('/api/v1/documents/'.$document->id)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ALREADY_DELETED');
    }

    #[Test]
    public function viewer_is_denied_when_attempting_to_delete_a_document(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'file',
            'filename' => 'vietato.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/vietato.txt',
            'tags' => [],
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->deleteJson('/api/v1/documents/'.$document->id)
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }

    #[Test]
    public function deleting_a_document_outside_the_tenant_returns_not_found(): void
    {
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();
        $viewerB = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail();

        $foreignDocument = Document::query()->create([
            'tenant_id' => $tenantB->id,
            'uploaded_by_user_id' => $viewerB->id,
            'source_type' => 'file',
            'filename' => 'tenant-b.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenantB->id.'/tenant-b.txt',
            'tags' => [],
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->deleteJson('/api/v1/documents/'.$foreignDocument->id)
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }
}
