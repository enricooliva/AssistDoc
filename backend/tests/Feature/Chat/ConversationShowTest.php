<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\MessageCitation;
use App\Models\User;
use App\Services\QdrantService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(QdrantService::class)->reset();
    }

    #[Test]
    public function it_returns_a_thread_with_messages_and_citations_for_the_owner(): void
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Policy tenant',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $userMessage = ChatMessage::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'conversation_id' => $conversation->id,
            'actor_type' => 'user',
            'body' => 'Come funziona l\'isolamento tenant?',
            'created_at' => now()->subMinute(),
        ]);

        $assistantMessage = ChatMessage::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'conversation_id' => $conversation->id,
            'actor_type' => 'assistant',
            'body' => 'L\'isolamento tenant viene applicato lato server.',
            'response_state' => 'answered',
            'created_at' => now(),
        ]);

        $document = Document::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'uploaded_by_user_id' => $viewer->id,
            'filename' => 'Manuale Sicurezza.pdf',
            'media_type' => 'application/pdf',
            'storage_path' => 'documents/manuale.pdf',
            'size_bytes' => 1024,
            'status' => 'ready',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $segment = DocumentSegment::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'document_id' => $document->id,
            'segment_index' => 0,
            'content_text' => 'AssistDoc applica isolamento tenant lato server a tutte le risorse.',
            'source_label' => 'Segmento 1',
            'searchable' => true,
        ]);

        MessageCitation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'chat_message_id' => $assistantMessage->id,
            'document_id' => $document->id,
            'document_segment_id' => $segment->id,
            'quote_text' => 'AssistDoc applica isolamento tenant lato server a tutte le risorse.',
            'source_label' => 'Segmento 1',
            'created_at' => now(),
        ]);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonPath('conversation.id', (string) $conversation->id)
            ->assertJsonPath('messages.0.id', (string) $userMessage->id)
            ->assertJsonPath('messages.1.responseState', 'answered')
            ->assertJsonPath('messages.1.citations.0.documentName', 'Manuale Sicurezza.pdf');
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
