<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\RetrievalModelProfile;
use App\Models\User;
use App\Models\ChunkingProfile;
use App\Services\AI\AiSearchService;
use App\Services\QdrantService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitChatMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Http::fake(fn () => Http::response(['result' => 'ok'], 200));
        app()->instance(QdrantService::class, new class extends QdrantService
        {
            public function __construct()
            {
            }

            public function reset(string $collection = null, ?int $vectorSize = null): array
            {
                return ['collection' => $collection ?? 'assistdoc_segments', 'vector_size' => $vectorSize ?? 4096];
            }

            public function search(
                array $vector,
                int $limit = 5,
                string $documentType = null,
                array $rules = [],
                string $name = null,
                ?int $vectorSize = null
            ): array {
                $tenantId = null;
                $retrievalModelProfileId = null;
                $chunkingProfileId = null;
                $tags = [];

                foreach ($rules as $rule) {
                    if (($rule['field'] ?? null) === 'tenant_id') {
                        $tenantId = (string) ($rule['value'] ?? '');
                    }

                    if (($rule['field'] ?? null) === 'retrieval_model_profile_id') {
                        $retrievalModelProfileId = (string) ($rule['value'] ?? '');
                    }

                    if (($rule['field'] ?? null) === 'chunking_profile_id') {
                        $chunkingProfileId = (string) ($rule['value'] ?? '');
                    }

                    if (($rule['field'] ?? null) === 'tags' && is_array($rule['value'] ?? null)) {
                        $tags = $rule['value'];
                    }
                }

                $query = DocumentSegment::query()
                    ->with(['document', 'retrievalModelProfile'])
                    ->where('searchable', true)
                    ->whereNull('retired_at');

                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }

                if ($retrievalModelProfileId !== null) {
                    $query->where('retrieval_model_profile_id', $retrievalModelProfileId);
                }

                if ($chunkingProfileId !== null) {
                    $query->where('chunking_profile_id', $chunkingProfileId);
                }

                if ($tags !== []) {
                    $query->whereHas('document', function ($builder) use ($tags): void {
                        $builder->where(function ($builder) use ($tags): void {
                            foreach ($tags as $tag) {
                                $builder->orWhereJsonContains('tags', $tag);
                            }
                        });
                    });
                }

                $matches = $query
                    ->limit($limit)
                    ->get()
                    ->map(static function (DocumentSegment $segment): array {
                        return [
                            'score' => 0.92,
                            'payload' => [
                                'document_id' => (string) $segment->document_id,
                                'segment_id' => (string) $segment->id,
                                'filename' => $segment->document?->filename ?? 'Documento',
                                'content_text' => $segment->content_text,
                                'source_label' => $segment->source_label,
                                'retrieval_model_profile_id' => $segment->retrieval_model_profile_id ? (string) $segment->retrieval_model_profile_id : null,
                                'embedding_model' => $segment->embedding_model ?? $segment->retrievalModelProfile?->embedding_model,
                            ],
                        ];
                    })
                    ->all();

                return ['result' => $matches];
            }

            public function upsert(array $points, string $name = null, ?int $vectorSize = null): array
            {
                return ['status' => 'ok'];
            }
        });
        app(QdrantService::class)->reset();
    }

    #[Test]
    public function it_returns_a_grounded_answer_with_citations_and_persists_the_exchange(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $retrievalProfile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $aiSearchService = $this->fakeAiSearchService();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.', (string) $retrievalProfile->id);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Come funziona l\'isolamento tenant?',
            ])
            ->assertOk()
            ->assertJsonPath('assistantMessage.responseState', 'answered')
            ->assertJsonPath('assistantMessage.generationModel', $retrievalProfile->generation_model)
            ->assertJsonPath('assistantMessage.citations.0.documentName', 'Manuale Tenant.pdf')
            ->assertJsonPath('retrievalModelProfileId', (string) $retrievalProfile->id)
            ->assertJsonPath('generationModel', $retrievalProfile->generation_model)
            ->assertJsonPath('promptContract', 'askLlamaWithContext');

        $this->assertCount(1, $aiSearchService->askCalls);
        $this->assertSame($retrievalProfile->generation_model, $aiSearchService->askCalls[0]['model']);

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
        $retrievalProfile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $aiSearchService = $this->fakeAiSearchService();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc conserva la cronologia dei messaggi in ordine cronologico.', (string) $retrievalProfile->id);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
            'question' => 'Come viene salvata la chat?',
        ])->assertOk();

        $this->assertCount(1, $aiSearchService->askCalls);
        $this->assertSame($retrievalProfile->generation_model, $aiSearchService->askCalls[0]['model']);

        $this->withToken($token)->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
            'question' => 'E cosa succede alle citazioni?',
        ])->assertOk();

        $this->assertCount(2, $aiSearchService->askCalls);
        $this->assertSame($retrievalProfile->generation_model, $aiSearchService->askCalls[1]['model']);

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
        $retrievalProfile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $aiSearchService = $this->fakeAiSearchService('model unavailable');
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.', (string) $retrievalProfile->id);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Che garanzie offre AssistDoc?',
            ])
            ->assertOk()
            ->assertJsonPath('assistantMessage.responseState', 'failed');

        $this->assertCount(1, $aiSearchService->askCalls);
        $this->assertSame($retrievalProfile->generation_model, $aiSearchService->askCalls[0]['model']);
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
        config()->set('rag.profile_preset', 'low_spec');
        $retrievalProfile = RetrievalModelProfile::query()->where('slug', config('rag.profiles.low_spec.slug'))->firstOrFail();
        $aiSearchService = $this->fakeAiSearchService();
        $this->prepareKnowledgeBase($viewer, 'AssistDoc applica isolamento tenant lato server e mostra citazioni verificabili.', (string) $retrievalProfile->id);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->postJson('/api/v1/chat/conversations/'.$conversation->id.'/messages', [
                'question' => 'Come funziona l\'isolamento tenant?',
            ])
            ->assertOk()
            ->assertJsonPath('retrievalModelProfileId', (string) $retrievalProfile->id)
            ->assertJsonPath('generationModel', $retrievalProfile->generation_model)
            ->assertJsonPath('promptContract', 'askLlamaWithContext');

        $this->assertCount(1, $aiSearchService->askCalls);
        $this->assertSame($retrievalProfile->generation_model, $aiSearchService->askCalls[0]['model']);
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

    private function prepareKnowledgeBase(User $viewer, string $content, ?string $retrievalModelProfileId = null): void
    {
        $retrievalModelProfileId ??= (string) RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail()->id;

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
            'retrieval_model_profile_id' => $retrievalModelProfileId,
            'chunking_profile_id' => ChunkingProfile::query()->where('slug', 'medium')->firstOrFail()->id,
            'segment_index' => 0,
            'content_text' => $content,
            'token_count' => 12,
            'source_label' => 'Segmento 1',
            'searchable' => true,
            'activated_at' => now(),
        ]);
    }

    private function fakeAiSearchService(?string $exceptionMessage = null): object
    {
        $service = new class($exceptionMessage) extends AiSearchService
        {
            public array $askCalls = [];

            public function __construct(private readonly ?string $exceptionMessage)
            {
            }

            public function askLlamaWithContext(
                string $query,
                string $context,
                ?string $model = null,
                bool $useReasoning = true,
                bool $queryIsFinalPrompt = false
            ): string {
                $this->askCalls[] = compact('query', 'context', 'model', 'useReasoning', 'queryIsFinalPrompt');

                if ($this->exceptionMessage !== null) {
                    throw new \RuntimeException($this->exceptionMessage);
                }

                return parent::askLlamaWithContext(
                    $query,
                    $context,
                    $model,
                    $useReasoning,
                    $queryIsFinalPrompt
                );
            }
        };

        app()->instance(AiSearchService::class, $service);

        return $service;
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
