<?php

namespace App\Services\Chat;

use App\DataTransferObjects\Chat\ChatConversationData;
use App\DataTransferObjects\Chat\ChatExchangeData;
use App\DataTransferObjects\Chat\ChatMessageData;
use App\Repositories\ChatConversationRepository;
use App\Repositories\MessageCitationRepository;
use App\Services\AI\AiSearchService;
use App\Services\Audit\AuditService;
use App\Services\Rag\RetrievalModelProfileService;
use App\Services\Search\SemanticSearchService;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        private readonly ChatConversationRepository $conversationRepository,
        private readonly SemanticSearchService $searchService,
        private readonly CitationService $citationService,
        private readonly MessageCitationRepository $messageCitationRepository,
        private readonly AiSearchService $aiSearchService,
        private readonly RetrievalModelProfileService $retrievalModelProfileService,
        private readonly AuditService $auditService,
    ) {
    }

    public function listConversations(string $tenantId, string $userId, int $page = 1, int $perPage = 25): array
    {
        return $this->conversationRepository->paginateForUser($tenantId, $userId, $page, $perPage);
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

    public function archiveConversation(string $tenantId, string $userId, string $conversationId): ?array
    {
        $conversation = $this->conversationRepository->findForUser($tenantId, $userId, $conversationId);

        if (! $conversation) {
            return null;
        }

        $conversation->status = 'archived';
        $conversation = $this->conversationRepository->save($conversation);

        $this->auditService->record('chat.conversation_archived', $tenantId, $userId, [
            'conversation_id' => $conversationId,
        ], 'success', 'chat_conversation', $conversationId);

        return ChatConversationData::fromModel($conversation)->toArray();
    }

    public function deleteConversation(string $tenantId, string $userId, string $conversationId): array
    {
        $conversation = $this->conversationRepository->findForUserIncludingDeleted($tenantId, $userId, $conversationId);

        if (! $conversation) {
            return [
                'status' => 'not_found',
                'conversationId' => $conversationId,
            ];
        }

        if ($conversation->deleted_at !== null) {
            return [
                'status' => 'already_deleted',
                'conversationId' => $conversationId,
                'deletedAt' => $conversation->deleted_at?->toIso8601String(),
            ];
        }

        $conversation = DB::transaction(function () use ($conversation, $tenantId, $userId, $conversationId) {
            $conversation = $this->conversationRepository->softDelete($conversation, $userId);

            $this->auditService->record('chat.conversation_deleted', $tenantId, $userId, [
                'conversation_id' => $conversationId,
                'deleted_at' => $conversation->deleted_at?->toIso8601String(),
                'deleted_by_user_id' => $userId,
            ], 'success', 'chat_conversation', $conversationId);

            return $conversation;
        });

        return [
            'conversationId' => (string) $conversation->id,
            'title' => $conversation->title,
            'status' => $conversation->status,
            'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
            'deletedAt' => $conversation->deleted_at?->toIso8601String(),
            'deletedBy' => $conversation->deletedBy ? [
                'id' => (string) $conversation->deletedBy->id,
                'fullName' => $conversation->deletedBy->name,
            ] : [
                'id' => $userId,
                'fullName' => 'Utente non disponibile',
            ],
            'removedFromList' => true,
        ];
    }

    public function answerQuestion(
        string $tenantId,
        string $userId,
        string $conversationId,
        string $question,
        ?string $retrievalModelProfileId = null,
        array $tags = [],
        ?string $chunkingProfileId = null,
    ): ?array
    {
        $conversation = $this->conversationRepository->findForUser($tenantId, $userId, $conversationId);

        if (! $conversation) {
            return null;
        }

        if ($conversation->status === 'archived') {
            return [
                'error' => [
                    'code' => 'CONVERSATION_ARCHIVED',
                    'message' => 'La conversazione è archiviata e non può ricevere nuovi messaggi.',
                ],
            ];
        }

        if ($conversation->deleted_at !== null) {
            return null;
        }

        if ($this->shouldGenerateTitle($conversation)) {
            $conversation->title = $this->generateTitleFromQuestion($question);
            $conversation = $this->conversationRepository->save($conversation);
        }

        $userMessage = $this->conversationRepository->createMessage($conversation, 'user', $question);
        $search = $this->searchService->query(
            $tenantId,
            $userId,
            $question,
            $retrievalModelProfileId,
            $tags,
            $chunkingProfileId,
        );
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
                $retrievalProfile = $this->retrievalModelProfileService->resolve(
                    $search['retrievalModelProfileId'] ?? null,
                );

                $assistantPayload = [
                    'body' => $this->aiSearchService->askLlamaWithContext(
                        $question,
                        $this->buildContext($supportingResults),
                        $this->aiSearchService->getGenerationModel($retrievalProfile),
                        true,
                        false,
                    ),
                    'responseState' => 'answered',
                ];
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
            'tags' => $tags,
            'chunking_profile_id' => $chunkingProfileId,
            'response_state' => $assistantPayload['responseState'],
            'supporting_references_returned' => $citations !== [],
            'result_count' => count($supportingResults),
        ], $outcome === 'failed' ? 'failure' : 'success', 'chat_conversation', $conversationId);

        return (new ChatExchangeData(
            conversationId: (string) $conversation->id,
            userMessage: ChatMessageData::fromModel($userMessage),
            assistantMessage: ChatMessageData::fromModel($assistantMessage),
            retrievalModelProfileId: $search['retrievalModelProfileId'] ?? null,
        ))->toArray();
    }

    private function buildContext(array $results): string
    {
        return implode("\n\n", array_map(static function (array $result): string {
            return sprintf(
                "[%s - %s]\n%s",
                $result['documentName'] ?? 'Documento',
                $result['sourceLabel'] ?? 'Segmento',
                trim((string) ($result['quoteText'] ?? $result['snippet'] ?? ''))
            );
        }, array_slice($results, 0, 4)));
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

    private function shouldGenerateTitle(\App\Models\ChatConversation $conversation): bool
    {
        return trim((string) $conversation->title) === '' || $conversation->title === 'Nuova conversazione';
    }

    private function generateTitleFromQuestion(string $question): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $question) ?? $question);

        if (mb_strlen($normalized) <= 72) {
            return $normalized;
        }

        return rtrim(mb_substr($normalized, 0, 69)).'...';
    }
}
