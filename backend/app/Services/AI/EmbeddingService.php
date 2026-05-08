<?php

namespace App\Services\AI;

class EmbeddingService
{
    public function embed(string $text): array
    {
        return [strlen($text), substr_count(strtolower($text), 'assistdoc'), 1];
    }
}

