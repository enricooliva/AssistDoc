<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\User;
use App\Models\ChunkingProfile;
use App\Services\QdrantService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitChatMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(QdrantService::class)->reset();
    }

    #[Test]
    public function it_returns_a_grounded_answer_with_citations_and_persists_the_exchange(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.');

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Come funziona l\'isolamento tenant?',
            ])
            ->assertOk()
            ->assertJsonPath('assistantMessage.responseState', 'answered')
            ->assertJsonPath('assistantMessage.citations.0.documentName', 'Manuale Tenant.pdf');

        $this->assertDatabaseCount('chat_messages', 2);
        $this->assertDatabaseCount('message_citations', 1);
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $viewer->tenant_id,
            'event_type' => 'chat.question_processed',
        ]);
        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'title' => 'Come funziona l\'isolamento tenant?',
        ]);
    }

    #[Test]
    public function it_supports_follow_up_questions_in_the_same_thread(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc conserva la cronologia dei messaggi in ordine cronologico.');

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
            'question' => 'Come viene salvata la chat?',
        ])->assertOk();

        $this->withToken($token)->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
            'question' => 'E cosa succede alle citazioni?',
        ])->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonCount(4, 'messages');
    }

    #[Test]
    public function it_returns_insufficient_information_when_no_relevant_support_is_available(): void
    {
        [, $conversation] = $this->prepareConversation();
        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Qual è il piano ferie del reparto estero?',
            ])
            ->assertOk()
            ->assertJsonPath('assistantMessage.responseState', 'insufficient_information')
            ->assertJsonPath('assistantMessage.body', 'Non ho trovato informazioni sufficienti nei documenti del tenant per rispondere in modo affidabile.')
            ->assertJsonPath('assistantMessage.citations', []);
    }

    #[Test]
    public function it_returns_a_failed_outcome_when_completion_cannot_finish(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.');

        app()->bind(\App\Services\AI\AiSearchService::class, fn (): \App\Services\AI\AiSearchService => new class extends \App\Services\AI\AiSearchService
        {
            public function askLlamaWithContext(string $query, string $context, ?string $model = null, bool $useReasoning = true, bool $queryIsFinalPrompt = false): string
            {
                throw new \RuntimeException('model unavailable');
            }
        });

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Che garanzie offre AssistDoc?',
            ])
            ->assertOk()
            ->assertJsonPath('assistantMessage.responseState', 'failed');
    }

    #[Test]
    public function it_rejects_new_messages_for_an_archived_conversation(): void
    {
        [$viewer, $conversation] = $this->prepareConversation(status: 'archived');
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.');

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Posso continuare la chat?',
            ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONVERSATION_ARCHIVED');
    }

    #[Test]
    public function it_allows_selecting_a_specific_retrieval_model_profile_for_the_chat_request(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.');

        $token = $this->login('viewer@assistdoc.local');
        $profile = \App\Models\RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Come funziona l\'isolamento tenant?',
            ])
            ->assertOk()
            ->assertJsonPath('retrievalModelProfileId', (string) $profile->id)
            ->assertJsonPath('promptContract', 'askLlamaWithContext');
    }

    private function prepareConversation(string $status = 'active'): array
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Nuova conversazione',
            'status' => $status,
            'last_message_at' => now(),
        ]);

        return [$viewer, $conversation];
    }

    private function prepareKnowledgeBase(User $viewer, string $content): void
    {
        $document = Document::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'uploaded_by_user_id' => $viewer->id,
            'filename' => 'Manuale Tenant.pdf',
            'media_type' => 'application/pdf',
            'storage_path' => 'documents/manuale-tenant.pdf',
            'size_bytes' => 1024,
            'status' => 'ready',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        DocumentSegment::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'document_id' => $document->id,
            'retrieval_model_profile_id' => \App\Models\RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail()->id,
            'chunking_profile_id' => ChunkingProfile::query()->where('slug', 'medium')->firstOrFail()->id,
            'segment_index' => 0,
            'content_text' => $content,
            'token_count' => 12,
            'source_label' => 'Segmento 1',
            'searchable' => true,
            'activated_at' => now(),
        ]);
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
