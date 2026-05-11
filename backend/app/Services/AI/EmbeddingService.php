<?php

namespace App\Services\AI;

class EmbeddingService
{
    public function embed(string $text): array
    {
        return [strlen($text), substr_count(strtolower($text), 'assistdoc'), 1];
    }

    public function generateEmbedding(string $text): array
    {
        return $this->embed($text);
    }

    public function getEmbeddingModel(): string
    {
        return 'assistdoc-demo-embedding-v1';
    }
}
