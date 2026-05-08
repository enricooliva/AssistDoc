<?php

namespace App\Services\Chat;

use App\Repositories\ChatConversationRepository;
use App\Services\AI\ChatCompletionService;
use App\Services\Audit\AuditService;
use App\Services\Search\SemanticSearchService;

class ChatService
{
    public function __construct(
        private readonly ChatConversationRepository $conversationRepository,
        private readonly SemanticSearchService $searchService,
        private readonly CitationService $citationService,
        private readonly ChatCompletionService $chatCompletionService,
        private readonly AuditService $auditService,
    ) {
    }

    public function listConversations(string $tenantId, string $userId): array
    {
        return [
            'items' => $this->conversationRepository->listForUser($tenantId, $userId),
            'page' => 1,
            'perPage' => 25,
            'total' => 1,
        ];
    }

    public function createConversation(string $tenantId, string $userId, ?string $title = null): array
    {
        return $this->conversationRepository->create($tenantId, $userId, $title);
    }

    public function answerQuestion(string $tenantId, string $userId, string $conversationId, string $question): array
    {
        $search = $this->searchService->query($tenantId, $userId, $question);
        $assistant = $this->chatCompletionService->answer($question, $search['results']);
        $citations = $assistant['responseState'] === 'completed'
            ? $this->citationService->fromSearchResults($search['results'])
            : [];

        $this->auditService->record('chat.question_submitted', $tenantId, $userId, [
            'conversation_id' => $conversationId,
            'question' => $question,
            'response_state' => $assistant['responseState'],
        ]);

        return [
            'conversationId' => $conversationId,
            'userMessage' => [
                'id' => 'msg-user-'.substr(md5($question), 0, 8),
                'actorType' => 'user',
                'body' => $question,
                'createdAt' => now()->toIso8601String(),
            ],
            'assistantMessage' => [
                'id' => 'msg-assistant-'.substr(md5($question.'assistant'), 0, 8),
                'actorType' => 'assistant',
                'body' => $assistant['body'],
                'responseState' => $assistant['responseState'],
                'citations' => $citations,
                'createdAt' => now()->toIso8601String(),
            ],
        ];
    }
}

