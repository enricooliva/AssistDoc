<?php

namespace Tests\Unit\Chat;

use App\Models\ChatConversation;
use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\RetrievalModelProfile;
use App\Models\User;
use App\Repositories\ChatConversationRepository;
use App\Repositories\MessageCitationRepository;
use App\Services\AI\AiSearchService;
use App\Services\Audit\AuditService;
use App\Services\Chat\ChatService;
use App\Services\Chat\CitationService;
use App\Services\Rag\RetrievalModelProfileService;
use App\Services\Search\SemanticSearchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_returns_insufficient_information_for_weak_support_without_calling_completion(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();

        $searchService = Mockery::mock(SemanticSearchService::class);
        $searchService->shouldReceive('query')->once()->andReturn([
            'query' => 'domanda',
            'results' => [[
                'documentId' => '1',
                'documentSegmentId' => '1',
                'documentName' => 'Manuale.pdf',
                'quoteText' => 'Supporto debole',
                'sourceLabel' => 'Segmento 1',
                'score' => 0.1,
            ]],
        ]);

        $completionService = Mockery::mock(AiSearchService::class);
        $completionService->shouldNotReceive('askLlamaWithContext');

        $service = new ChatService(
            app(ChatConversationRepository::class),
            $searchService,
            app(CitationService::class),
            app(MessageCitationRepository::class),
            $completionService,
            app(RetrievalModelProfileService::class),
            app(AuditService::class),
        );

        $response = $service->answerQuestion((string) $viewer->tenant_id, (string) $viewer->id, (string) $conversation->id, 'domanda');

        $this->assertSame('insufficient_information', $response['assistantMessage']['responseState']);
    }

    #[Test]
    public function it_returns_failed_when_completion_raises_an_exception(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $retrievalProfile = $this->ensureRetrievalProfile(
            (string) config('rag.profiles.default.slug', 'qwen'),
            (string) config('rag.profiles.default.generation_model', 'qwen3'),
        );
        $knowledgeBase = $this->prepareKnowledgeBase($viewer, $retrievalProfile);

        $searchService = Mockery::mock(SemanticSearchService::class);
        $searchService->shouldReceive('query')
            ->once()
            ->with((string) $viewer->tenant_id, (string) $viewer->id, 'Come funziona AssistDoc?', null, [], null)
            ->andReturn([
                'query' => 'Come funziona AssistDoc?',
                'retrievalModelProfileId' => (string) $retrievalProfile->id,
                'results' => [[
                    'documentId' => $knowledgeBase['documentId'],
                    'documentSegmentId' => $knowledgeBase['documentSegmentId'],
                    'documentName' => 'Manuale Tenant.pdf',
                    'quoteText' => 'AssistDoc applica isolamento tenant lato server.',
                    'sourceLabel' => 'Segmento 1',
                    'score' => 0.9,
                ]],
            ]);

        $service = new ChatService(
            app(ChatConversationRepository::class),
            $searchService,
            app(CitationService::class),
            app(MessageCitationRepository::class),
            new class extends AiSearchService
            {
                public function askLlamaWithContext(
                    string $query,
                    string $context,
                    ?string $model = null,
                    bool $useReasoning = true,
                    bool $queryIsFinalPrompt = false
                ): string
                {
                    throw new \RuntimeException('llm unavailable');
                }
            },
            app(RetrievalModelProfileService::class),
            app(AuditService::class),
        );

        $response = $service->answerQuestion((string) $viewer->tenant_id, (string) $viewer->id, (string) $conversation->id, 'Come funziona AssistDoc?');

        $this->assertSame('failed', $response['assistantMessage']['responseState']);
    }

    #[Test]
    public function it_uses_the_configured_retrieval_profile_for_semantic_search(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $retrievalProfile = $this->ensureRetrievalProfile(
            (string) config('rag.profiles.default.slug', 'qwen'),
            (string) config('rag.profiles.default.generation_model', 'qwen3'),
        );

        $searchService = Mockery::mock(SemanticSearchService::class);
        $searchService->shouldReceive('query')
            ->once()
            ->with((string) $viewer->tenant_id, (string) $viewer->id, 'Come funziona AssistDoc?', null, [], null)
            ->andReturn([
                'query' => 'Come funziona AssistDoc?',
                'retrievalModelProfileId' => (string) $retrievalProfile->id,
                'results' => [[
                    'documentId' => '1',
                    'documentSegmentId' => '1',
                    'documentName' => 'Manuale Tenant.pdf',
                    'quoteText' => 'AssistDoc applica isolamento tenant lato server.',
                    'sourceLabel' => 'Segmento 1',
                    'score' => 0.9,
                ]],
            ]);

        $completionService = Mockery::mock(AiSearchService::class);
        $completionService->shouldReceive('getGenerationModel')
            ->once()
            ->with(Mockery::type(\App\Models\RetrievalModelProfile::class))
            ->andReturn($retrievalProfile->generation_model);
        $completionService->shouldReceive('askLlamaWithContext')
            ->once()
            ->with(
                'Come funziona AssistDoc?',
                Mockery::type('string'),
                $retrievalProfile->generation_model,
                true,
                false,
            )
            ->andReturn('AssistDoc applica isolamento tenant lato server.');

        $citationService = Mockery::mock(CitationService::class);
        $citationService->shouldReceive('fromSearchResults')
            ->once()
            ->andReturn([]);

        $service = new ChatService(
            app(ChatConversationRepository::class),
            $searchService,
            $citationService,
            app(MessageCitationRepository::class),
            $completionService,
            app(RetrievalModelProfileService::class),
            app(AuditService::class),
        );

        $response = $service->answerQuestion(
            (string) $viewer->tenant_id,
            (string) $viewer->id,
            (string) $conversation->id,
            'Come funziona AssistDoc?',
        );

        $this->assertSame((string) $retrievalProfile->id, $response['retrievalModelProfileId']);
        $this->assertSame($retrievalProfile->generation_model, $response['generationModel']);
        $this->assertSame('answered', $response['assistantMessage']['responseState']);
        $this->assertSame($retrievalProfile->generation_model, $response['assistantMessage']['generationModel']);
    }

    private function prepareConversation(): array
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Chat unit test',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        return [$viewer, $conversation];
    }

    private function prepareKnowledgeBase(User $viewer, RetrievalModelProfile $retrievalProfile): array
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

        $segment = DocumentSegment::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'document_id' => $document->id,
            'retrieval_model_profile_id' => $retrievalProfile->id,
            'chunking_profile_id' => ChunkingProfile::query()->where('slug', 'medium')->firstOrFail()->id,
            'segment_index' => 0,
            'content_text' => 'AssistDoc applica isolamento tenant lato server.',
            'source_label' => 'Segmento 1',
            'searchable' => true,
        ]);

        return [
            'documentId' => (string) $document->id,
            'documentSegmentId' => (string) $segment->id,
        ];
    }

    private function ensureRetrievalProfile(string $slug, string $generationModel): RetrievalModelProfile
    {
        return RetrievalModelProfile::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $slug,
                'generation_model' => $generationModel,
                'embedding_model' => 'qwen3-embedding',
                'tokenizer_key' => 'qwen3',
                'token_window' => 40000,
                'embedding_dimensions' => 4096,
                'available_for_new_runs' => true,
                'effective_from' => now(),
            ],
        );
    }
}
