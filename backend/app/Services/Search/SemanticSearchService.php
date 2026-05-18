<?php

namespace App\Services\Search;

use App\Services\Rag\RetrievalModelProfileService;
use App\Services\AI\EmbeddingService;
use App\Services\QdrantService;
use App\Repositories\DocumentSegmentRepository;
use App\Repositories\SearchQueryRepository;

class SemanticSearchService
{
    public function __construct(
        private readonly DocumentSegmentRepository $segmentRepository,
        private readonly SearchQueryRepository $queryRepository,
        private readonly EmbeddingService $embeddingService,
        private readonly QdrantService $qdrantService,
        private readonly RetrievalModelProfileService $retrievalModelProfileService,
    ) {
    }

    public function query(string $tenantId, string $userId, string $query, ?string $profileId = null): array
    {
        $profile = $this->retrievalModelProfileService->resolve($profileId);
        $vectorMatches = $this->qdrantService->search('documents', $this->embeddingService->embed($query, $profile), [
            'must' => [
                ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
                ['key' => 'document_status', 'match' => ['value' => 'ready']],
                ['key' => 'retrieval_model_profile_id', 'match' => ['value' => (string) $profile->id]],
            ],
        ], 5);

        $results = $vectorMatches !== []
            ? array_map(fn (array $match): array => [
                'documentId' => $match['payload']['document_id'],
                'documentSegmentId' => $match['payload']['segment_id'] ?? null,
                'documentName' => $match['payload']['filename'],
                'snippet' => mb_substr($match['payload']['content_text'], 0, 240),
                'quoteText' => mb_substr($match['payload']['content_text'], 0, 240),
                'score' => round($match['score'], 4),
                'sourceLabel' => $match['payload']['source_label'],
                'retrievalModelProfileId' => $match['payload']['retrieval_model_profile_id'] ?? null,
                'tenantId' => $tenantId,
                'query' => $query,
            ], $vectorMatches)
            : $this->segmentRepository->semanticSearch($tenantId, $query, (string) $profile->id);

        usort($results, static fn (array $left, array $right): int => ($right['score'] <=> $left['score']));

        $this->queryRepository->log($tenantId, $userId, $query, count($results));

        return [
            'query' => $query,
            'retrievalModelProfileId' => (string) $profile->id,
            'results' => $results,
        ];
    }
}
