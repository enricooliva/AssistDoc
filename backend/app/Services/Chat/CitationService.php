<?php

namespace App\Services\Chat;

class CitationService
{
    public function fromSearchResults(array $results): array
    {
        return array_map(static fn (array $result): array => [
            'documentId' => $result['documentId'],
            'documentName' => $result['documentName'],
            'sourceLabel' => $result['sourceLabel'],
            'quoteText' => $result['snippet'],
        ], $results);
    }
}

