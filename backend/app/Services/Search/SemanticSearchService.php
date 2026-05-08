<?php

namespace App\Services\Search;

use App\Repositories\DocumentSegmentRepository;
use App\Repositories\SearchQueryRepository;

class SemanticSearchService
{
    public function __construct(
        private readonly DocumentSegmentRepository $segmentRepository,
        private readonly SearchQueryRepository $queryRepository,
    ) {
    }

    public function query(string $tenantId, string $userId, string $query): array
    {
        $results = $this->segmentRepository->semanticSearch($tenantId, $query);
        $this->queryRepository->log($tenantId, $userId, $query, count($results));

        return [
            'query' => $query,
            'results' => $results,
        ];
    }
}

