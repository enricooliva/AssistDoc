<?php

namespace App\Services\AI;

use App\Models\ChunkingProfile;
use App\Models\RetrievalModelProfile;

class TokenizerService
{
    public function countTokens(string $text, string $tokenizerKey = 'default'): int
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($normalized === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $normalized) ?: []);
    }

    public function splitText(string $text, RetrievalModelProfile $profile, ChunkingProfile $chunkingProfile): array
    {
        $chunkSize = (int) $chunkingProfile->chunk_size_tokens;
        $overlap = (int) $chunkingProfile->overlap_tokens;

        if ($chunkSize <= 0 || $overlap < 0 || $overlap >= $chunkSize) {
            throw new \RuntimeException('Il profilo di segmentazione non è valido per la preparazione del documento.');
        }

        if ($chunkSize > (int) $profile->token_window) {
            throw new \RuntimeException(sprintf(
                'Il profilo di segmentazione supera il limite di %d token del modello selezionato.',
                $profile->token_window,
            ));
        }

        $tokens = preg_split('/\s+/u', trim(preg_replace('/\s+/u', ' ', $text) ?? $text)) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));
        if ($tokens === []) {
            return [];
        }

        $chunks = [];
        $start = 0;

        while ($start < count($tokens)) {
            $chunkTokens = array_slice($tokens, $start, $chunkSize);
            if ($chunkTokens === []) {
                break;
            }

            $chunks[] = [
                'content' => implode(' ', $chunkTokens),
                'token_count' => count($chunkTokens),
            ];

            $nextStart = $start + $chunkSize - $overlap;
            $start = $nextStart <= $start ? $start + $chunkSize : $nextStart;
        }

        return $chunks;
    }
}
