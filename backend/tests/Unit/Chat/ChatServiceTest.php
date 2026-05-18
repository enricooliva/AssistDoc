<?php

namespace Tests\Unit\Chat;

use App\Models\ChatConversation;
use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\User;
use App\Repositories\ChatConversationRepository;
use App\Repositories\MessageCitationRepository;
use App\Services\AI\ChatCompletionService;
use App\Services\Audit\AuditService;
use App\Services\Chat\ChatService;
use App\Services\Chat\CitationService;
use App\Services\QdrantService;
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
        app(QdrantService::class)->reset();
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

        $completionService = Mockery::mock(ChatCompletionService::class);
        $completionService->shouldNotReceive('answer');

        $service = new ChatService(
            app(ChatConversationRepository::class),
            $searchService,
            app(CitationService::class),
            app(MessageCitationRepository::class),
            $completionService,
            app(AuditService::class),
        );

        $response = $service->answerQuestion((string) $viewer->tenant_id, (string) $viewer->id, (string) $conversation->id, 'domanda');

        $this->assertSame('insufficient_information', $response['assistantMessage']['responseState']);
    }

    #[Test]
    public function it_returns_failed_when_completion_raises_an_exception(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $this->prepareKnowledgeBase($viewer);

        $service = new ChatService(
            app(ChatConversationRepository::class),
            app(SemanticSearchService::class),
            app(CitationService::class),
            app(MessageCitationRepository::class),
            new class extends ChatCompletionService
            {
                public function answer(string $question, array $results): array
                {
                    throw new \RuntimeException('llm unavailable');
                }
            },
            app(AuditService::class),
        );

        $response = $service->answerQuestion((string) $viewer->tenant_id, (string) $viewer->id, (string) $conversation->id, 'Come funziona AssistDoc?');

        $this->assertSame('failed', $response['assistantMessage']['responseState']);
    }

    #[Test]
    public function it_uses_the_configured_retrieval_profile_for_semantic_search(): void
    {
        [$viewer, $conversation] = $this->prepareConversation();
        $profile = \App\Models\RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();

        $searchService = Mockery::mock(SemanticSearchService::class);
        $searchService->shouldReceive('query')
            ->once()
            ->with((string) $viewer->tenant_id, (string) $viewer->id, 'Come funziona AssistDoc?', null)
            ->andReturn([
                'query' => 'Come funziona AssistDoc?',
                'retrievalModelProfileId' => (string) $profile->id,
                'results' => [[
                    'documentId' => '1',
                    'documentSegmentId' => '1',
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
            app(ChatCompletionService::class),
            app(AuditService::class),
        );

        $response = $service->answerQuestion(
            (string) $viewer->tenant_id,
            (string) $viewer->id,
            (string) $conversation->id,
            'Come funziona AssistDoc?',
        );

        $this->assertSame((string) $profile->id, $response['retrievalModelProfileId']);
        $this->assertSame('answered', $response['assistantMessage']['responseState']);
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

    private function prepareKnowledgeBase(User $viewer): void
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
            'content_text' => 'AssistDoc applica isolamento tenant lato server.',
            'source_label' => 'Segmento 1',
            'searchable' => true,
        ]);
    }
}
