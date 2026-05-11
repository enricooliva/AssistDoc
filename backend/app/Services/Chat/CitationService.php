<?php

namespace App\Services\Chat;

class CitationService
{
    public function fromSearchResults(array $results): array
    {
        $citations = [];
        $seen = [];

        foreach ($results as $result) {
            $documentSegmentId = $result['documentSegmentId'] ?? null;

            if (! $documentSegmentId || isset($seen[$documentSegmentId])) {
                continue;
            }

            $quoteText = trim((string) ($result['quoteText'] ?? $result['snippet'] ?? ''));

            if ($quoteText === '') {
                continue;
            }

            $seen[$documentSegmentId] = true;
            $citations[] = [
                'documentId' => (string) $result['documentId'],
                'documentSegmentId' => (string) $documentSegmentId,
                'documentName' => $result['documentName'],
                'sourceLabel' => $result['sourceLabel'],
                'quoteText' => mb_substr($quoteText, 0, 280),
            ];
        }

        return array_slice($citations, 0, 3);
    }
}
