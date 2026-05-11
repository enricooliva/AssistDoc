<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\User;
use App\Services\AI\ChatCompletionService;
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
            ->assertJsonPath('assistantMessage.citations', []);
    }

    #[Test]
    public function it_returns_a_failed_outcome_when_completion_cannot_finish(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.');

        app()->bind(ChatCompletionService::class, fn (): ChatCompletionService => new class extends ChatCompletionService
        {
            public function answer(string $question, array $results): array
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

    private function prepareConversation(): array
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Chat test',
            'status' => 'active',
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
            'segment_index' => 0,
            'content_text' => $content,
            'source_label' => 'Segmento 1',
            'searchable' => true,
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
