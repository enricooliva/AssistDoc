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

    public function query(
        string $tenantId,
        string $userId,
        string $query,
        ?string $profileId = null,
        array $tags = [],
        ?string $chunkingProfileId = null,
    ): array
    {
        $profile = $this->retrievalModelProfileService->resolve($profileId);
        $rules = [
            ['field' => 'tenant_id', 'operator' => '=', 'value' => $tenantId],
            ['field' => 'document_status', 'operator' => '=', 'value' => 'ready'],
            ['field' => 'retrieval_model_profile_id', 'operator' => '=', 'value' => (string) $profile->id],
        ];

        if ($chunkingProfileId !== null && $chunkingProfileId !== '') {
            $rules[] = ['field' => 'chunking_profile_id', 'operator' => '=', 'value' => $chunkingProfileId];
        }

        if ($tags !== []) {
            $rules[] = ['field' => 'tags', 'operator' => 'in', 'value' => $tags];
        }

        $vectorMatches = $this->normalizeVectorMatches($this->qdrantService->search(
            $this->embeddingService->embed($query, $profile),
            5,
            null,
            $rules,
            null,
            $this->embeddingService->getEmbeddingDimensions($profile)            
        ));

        $results = $vectorMatches !== []
            ? array_map(fn (array $match): array => [
                'documentId' => $match['payload']['document_id'],
                'documentSegmentId' => $match['payload']['segment_id'] ?? null,
                'documentName' => $match['payload']['filename'],
                'snippet' => $match['payload']['content_text'],
                'quoteText' => $match['payload']['content_text'],
                'score' => round($match['score'], 4),
                'sourceLabel' => $match['payload']['source_label'],
                'retrievalModelProfileId' => $match['payload']['retrieval_model_profile_id'] ?? null,
                'embeddingModel' => $match['payload']['embedding_model'] ?? null,
                'tenantId' => $tenantId,
                'query' => $query, 
            ], $vectorMatches)
            : $this->segmentRepository->semanticSearch($tenantId, $query, (string) $profile->id, $tags, $chunkingProfileId);

        usort($results, static fn (array $left, array $right): int => ($right['score'] <=> $left['score']));

        $this->queryRepository->log($tenantId, $userId, $query, count($results));

        return [
            'query' => $query,
            'retrievalModelProfileId' => (string) $profile->id,
            'results' => $results,
        ];
    }

    private function normalizeVectorMatches(array $response): array
    {
        if (isset($response['result']) && is_array($response['result'])) {
            return array_values(array_filter($response['result'], 'is_array'));
        }

        return array_values(array_filter($response, 'is_array'));
    }
}
