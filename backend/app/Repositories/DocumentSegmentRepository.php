<?php

namespace App\Repositories;

class DocumentSegmentRepository
{
    public function semanticSearch(string $tenantId, string $query): array
    {
        return [[
            'documentId' => 'doc-001',
            'documentName' => 'Manuale Aziendale.pdf',
            'snippet' => 'AssistDoc usa isolamento tenant lato server per tutte le risorse.',
            'score' => 0.96,
            'sourceLabel' => 'Pagina 4',
            'tenantId' => $tenantId,
            'query' => $query,
        ]];
    }
}

