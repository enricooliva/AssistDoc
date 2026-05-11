<?php

namespace App\Services\Chat;

use App\DataTransferObjects\Chat\ChatConversationData;
use App\DataTransferObjects\Chat\ChatExchangeData;
use App\DataTransferObjects\Chat\ChatMessageData;
use App\Repositories\ChatConversationRepository;
use App\Repositories\MessageCitationRepository;
use App\Services\AI\ChatCompletionService;
use App\Services\Audit\AuditService;
use App\Services\Search\SemanticSearchService;

class ChatService
{
    public function __construct(
        private readonly ChatConversationRepository $conversationRepository,
        private readonly SemanticSearchService $searchService,
        private readonly CitationService $citationService,
        private readonly MessageCitationRepository $messageCitationRepository,
        private readonly ChatCompletionService $chatCompletionService,
        private readonly AuditService $auditService,
    ) {
    }

    public function listConversations(string $tenantId, string $userId): array
    {
        $items = $this->conversationRepository->listForUser($tenantId, $userId);

        return [
            'items' => $items,
            'page' => 1,
            'perPage' => 25,
            'total' => $this->conversationRepository->countForUser($tenantId, $userId),
        ];
    }

    public function createConversation(string $tenantId, string $userId, ?string $title = null): array
    {
        $conversation = $this->conversationRepository->create($tenantId, $userId, $title);

        return ChatConversationData::fromModel($conversation)->toArray();
    }

    public function showConversation(string $tenantId, string $userId, string $conversationId): ?array
    {
        $conversation = $this->conversationRepository->getThreadForUser($tenantId, $userId, $conversationId);

        if (! $conversation) {
            return null;
        }

        return [
            'conversation' => ChatConversationData::fromModel($conversation)->toArray(),
            'messages' => $conversation->messages
                ->map(fn ($message): array => ChatMessageData::fromModel($message)->toArray())
                ->all(),
        ];
    }

    public function answerQuestion(string $tenantId, string $userId, string $conversationId, string $question): ?array
    {
        $conversation = $this->conversationRepository->findForUser($tenantId, $userId, $conversationId);

        if (! $conversation) {
            return null;
        }

        $userMessage = $this->conversationRepository->createMessage($conversation, 'user', $question);
        $search = $this->searchService->query($tenantId, $userId, $question);
        $supportingResults = $search['results'];

        if (! $this->hasSufficientSupport($supportingResults)) {
            $assistantPayload = [
                'body' => 'Non ho trovato informazioni sufficienti nei documenti del tenant per rispondere in modo affidabile.',
                'responseState' => 'insufficient_information',
            ];
            $citations = [];
            $outcome = 'insufficient_information';
        } else {
            try {
                $assistantPayload = $this->chatCompletionService->answer($question, $supportingResults);
                $citations = $this->citationService->fromSearchResults($supportingResults);
                $outcome = $assistantPayload['responseState'];
            } catch (\Throwable) {
                $assistantPayload = [
                    'body' => 'Si è verificato un errore durante l\'elaborazione della risposta. Riprova tra poco.',
                    'responseState' => 'failed',
                ];
                $citations = [];
                $outcome = 'failed';
            }
        }

        $assistantMessage = $this->conversationRepository->createMessage(
            $conversation,
            'assistant',
            $assistantPayload['body'],
            $assistantPayload['responseState']
        );

        if ($citations !== []) {
            $this->messageCitationRepository->replaceForMessage($assistantMessage, $citations);
        }

        $assistantMessage->load(['citations.document']);

        $this->auditService->record('chat.question_processed', $tenantId, $userId, [
            'conversation_id' => $conversationId,
            'question' => $question,
            'response_state' => $assistantPayload['responseState'],
            'supporting_references_returned' => $citations !== [],
            'result_count' => count($supportingResults),
        ], $outcome === 'failed' ? 'failure' : 'success', 'chat_conversation', $conversationId);

        return (new ChatExchangeData(
            conversationId: (string) $conversation->id,
            userMessage: ChatMessageData::fromModel($userMessage),
            assistantMessage: ChatMessageData::fromModel($assistantMessage),
        ))->toArray();
    }

    private function hasSufficientSupport(array $results): bool
    {
        if ($results === []) {
            return false;
        }

        $topScore = (float) ($results[0]['score'] ?? 0);
        $topSnippet = trim((string) ($results[0]['quoteText'] ?? $results[0]['snippet'] ?? ''));

        return $topScore >= 0.35 && $topSnippet !== '';
    }
}
