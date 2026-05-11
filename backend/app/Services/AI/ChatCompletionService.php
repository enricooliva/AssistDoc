<?php

namespace App\Services\AI;

class ChatCompletionService
{
    public function answer(string $question, array $results): array
    {
        if ($results === []) {
            throw new \RuntimeException('Impossibile generare una risposta senza evidenze.');
        }

        $topResults = array_slice($results, 0, 2);
        $leadEvidence = trim((string) ($topResults[0]['quoteText'] ?? $topResults[0]['snippet'] ?? ''));
        $documents = implode(', ', array_values(array_unique(array_map(
            static fn (array $result): string => $result['documentName'],
            $topResults
        ))));

        return [
            'body' => sprintf(
                'Nei documenti del tenant risulta che %s. Risposta elaborata consultando %s e limitata alle informazioni effettivamente recuperate.',
                rtrim($leadEvidence, " ."),
                $documents
            ),
            'responseState' => 'answered',
        ];
    }
}
