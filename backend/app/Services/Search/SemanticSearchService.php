<?php

namespace App\Services\Search;

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
    ) {
    }

    public function query(string $tenantId, string $userId, string $query): array
    {
        $vectorMatches = $this->qdrantService->search('documents', $this->embeddingService->embed($query), [
            'must' => [
                ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
                ['key' => 'document_status', 'match' => ['value' => 'ready']],
            ],
        ], 5);

        $results = $vectorMatches !== []
            ? array_map(fn (array $match): array => [
                'documentId' => $match['payload']['document_id'],
                'documentName' => $match['payload']['filename'],
                'snippet' => mb_substr($match['payload']['content_text'], 0, 240),
                'score' => round($match['score'], 4),
                'sourceLabel' => $match['payload']['source_label'],
                'tenantId' => $tenantId,
                'query' => $query,
            ], $vectorMatches)
            : $this->segmentRepository->semanticSearch($tenantId, $query);

        $this->queryRepository->log($tenantId, $userId, $query, count($results));

        return [
            'query' => $query,
            'results' => $results,
        ];
    }
}
