<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmbeddingService
{
    public function embed(string $text): array
    {
        $normalized = $this->prepareInput($text);

        if ($normalized === '') {
            return [];
        }

        if (app()->environment('testing')) {
            return $this->fakeEmbedding($normalized);
        }

        $response = Http::timeout(30)->post($this->getEmbeddingEndpoint(), [
            'model' => $this->getEmbeddingModel(),
            'prompt' => $normalized,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(sprintf(
                'Embedding request failed for model %s at %s.',
                $this->getEmbeddingModel(),
                $this->getEmbeddingEndpoint(),
            ));
        }

        $embedding = $response->json('embedding') ?? $response->json('embeddings.0');

        if (! is_array($embedding) || count($embedding) !== $this->getEmbeddingDimensions()) {
            throw new \RuntimeException(sprintf(
                'Embedding response for model %s did not return %d dimensions.',
                $this->getEmbeddingModel(),
                $this->getEmbeddingDimensions(),
            ));
        }

        return array_map(static fn ($value): float => (float) $value, $embedding);
    }

    public function generateEmbedding(string $text): array
    {
        return $this->embed($text);
    }

    public function getEmbeddingModel(): string
    {
        return (string) config('services.ollama.embedding_model', 'mxbai-embed-large');
    }

    public function getEmbeddingEndpoint(): string
    {
        return (string) config('services.ollama.embedding_url', 'http://192.168.5.137:11434/api/embeddings');
    }

    public function getEmbeddingDimensions(): int
    {
        return (int) config('services.ollama.embedding_dimensions', 1024);
    }

    public function getEmbeddingMaxInputChars(): int
    {
        return (int) config('services.ollama.embedding_max_input_chars', 1800);
    }

    private function prepareInput(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        if ($normalized === '') {
            return '';
        }

        return Str::of($normalized)
            ->limit($this->getEmbeddingMaxInputChars(), '')
            ->rtrim()
            ->toString();
    }

    private function fakeEmbedding(string $text): array
    {
        $vector = [];

        for ($index = 0; $index < $this->getEmbeddingDimensions(); $index++) {
            $hash = hash('sha256', $text.':'.$index);
            $value = hexdec(substr($hash, 0, 8)) / 0xffffffff;
            $vector[] = ($value * 2) - 1;
        }

        return $vector;
    }
}
