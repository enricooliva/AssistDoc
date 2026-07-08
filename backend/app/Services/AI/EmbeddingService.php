<?php

namespace App\Services\AI;

use App\Models\RetrievalModelProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmbeddingService
{
    public function embed(string $text, ?RetrievalModelProfile $profile = null): array
    {
        $normalized = $this->prepareInput($text, $profile);

        if ($normalized === '') {
            return [];
        }

        if (app()->environment('testing')) {
            return $this->fakeEmbedding($normalized, $profile);
        }

        $response = Http::timeout(120)->post($this->getEmbeddingEndpoint($profile), [
            'model' => $this->getEmbeddingModel($profile),
            'input' => $normalized,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(sprintf(
                'Embedding request failed for model %s at %s.',
                $this->getEmbeddingModel($profile),
                $this->getEmbeddingEndpoint($profile),
            ));
        }

        $embedding = $response->json('embedding') ?? $response->json('embeddings.0');

        if (! is_array($embedding) || count($embedding) !== $this->getEmbeddingDimensions($profile)) {
            throw new \RuntimeException(sprintf(
                'Embedding response for model %s did not return %d dimensions.',
                $this->getEmbeddingModel($profile),
                $this->getEmbeddingDimensions($profile),
            ));
        }

        return array_map(static fn ($value): float => (float) $value, $embedding);
    }

    public function generateEmbedding(string $text, ?RetrievalModelProfile $profile = null): array
    {
        return $this->embed($text, $profile);
    }

    public function getEmbeddingModel(?RetrievalModelProfile $profile = null): string
    {
        return $profile?->embedding_model
            ?? (string) config('rag.profiles.default.embedding_model', 'qwen3-embedding');
    }

    public function getEmbeddingEndpoint(?RetrievalModelProfile $profile = null): string
    {
        return (string) config('services.ollama.embedding_url', 'http://192.168.5.137:11434/api/embed');
    }

    public function getEmbeddingDimensions(?RetrievalModelProfile $profile = null): int
    {
        return $profile?->embedding_dimensions
            ?? (int) config('rag.profiles.default.embedding_dimensions', 4096);
    }

    public function getEmbeddingMaxInputChars(?RetrievalModelProfile $profile = null): int
    {
        if ($profile) {
            return max(1800, (int) $profile->token_window * 8);
        }

        return max(
            (int) config('services.ollama.embedding_max_input_chars', 1800),
            (int) config('rag.profiles.default.token_window', 40000) * 8,
        );
    }

    private function prepareInput(string $text, ?RetrievalModelProfile $profile = null): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        if ($normalized === '') {
            return '';
        }

        return Str::of($normalized)
            ->limit($this->getEmbeddingMaxInputChars($profile), '')
            ->rtrim()
            ->toString();
    }

    private function fakeEmbedding(string $text, ?RetrievalModelProfile $profile = null): array
    {
        $vector = [];

        for ($index = 0; $index < $this->getEmbeddingDimensions($profile); $index++) {
            $hash = hash('sha256', $text.':'.$index);
            $value = hexdec(substr($hash, 0, 8)) / 0xffffffff;
            $vector[] = ($value * 2) - 1;
        }

        return $vector;
    }
}
