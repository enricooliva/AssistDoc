<?php

namespace App\Repositories;

class SearchQueryRepository
{
    public function log(string $tenantId, string $userId, string $query, int $resultCount): array
    {
        return compact('tenantId', 'userId', 'query', 'resultCount');
    }
}

