<?php

namespace App\Services\Audit;

use App\DataTransferObjects\AuditEventFilters;
use App\Repositories\AuditEventRepository;

class AuditQueryService
{
    public function __construct(private readonly AuditEventRepository $repository)
    {
    }

    public function index(string $tenantId, AuditEventFilters $filters): array
    {
        $items = $this->repository->query($tenantId, [
            'actor' => $filters->actor,
            'eventType' => $filters->eventType,
            'outcome' => $filters->outcome,
        ]);

        return [
            'items' => $items,
            'page' => 1,
            'perPage' => 25,
            'total' => count($items),
        ];
    }
}

