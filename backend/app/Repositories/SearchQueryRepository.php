<?php

namespace App\Repositories;

use App\Models\SearchQuery;

class SearchQueryRepository
{
    public function log(string $tenantId, string $userId, string $query, int $resultCount): array
    {
        $searchQuery = SearchQuery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'query_text' => $query,
            'result_count' => $resultCount,
            'created_at' => now(),
        ]);

        return [
            'tenantId' => (string) $searchQuery->tenant_id,
            'userId' => (string) $searchQuery->user_id,
            'query' => $searchQuery->query_text,
            'resultCount' => $searchQuery->result_count,
        ];
    }
}
