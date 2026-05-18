<?php

namespace App\Services\Documents;

use App\Models\ChunkPreparationRun;
use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\RetrievalModelProfile;
use App\Services\AI\EmbeddingService;
use App\Services\AI\TokenizerService;
use App\Services\QdrantService;
use Illuminate\Support\Str;

class DocumentIndexerService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly QdrantService $qdrantService,
        private readonly TokenizerService $tokenizerService,
    ) {
    }

    public function buildSegments(
        Document $document,
        string $text,
        ?RetrievalModelProfile $profile = null,
        ?ChunkingProfile $chunkingProfile = null,
        ?ChunkPreparationRun $run = null,
    ): array
    {
        $normalizedText = $this->normalizeDocumentText($text);
        if ($profile && $chunkingProfile) {
            $chunks = $this->tokenizerService->splitText($normalizedText, $profile, $chunkingProfile);
        } else {
            $chunks = array_map(fn (string $chunk): array => [
                'content' => $chunk,
                'token_count' => $this->tokenizerService->countTokens($chunk),
            ], $this->chunkTextWithOverlap($normalizedText, 1200, 180));
        }
        $segments = [];

        foreach ($chunks as $index => $chunk) {
            $trimmed = trim($chunk['content']);
            if ($trimmed === '') {
                continue;
            }

            $segments[] = [
                'tenant_id' => $document->tenant_id,
                'document_id' => $document->id,
                'chunk_preparation_run_id' => $run?->id,
                'retrieval_model_profile_id' => $profile?->id,
                'chunking_profile_id' => $chunkingProfile?->id,
                'segment_index' => $index,
                'content_text' => $trimmed,
                'token_count' => $chunk['token_count'],
                'source_label' => sprintf('Segmento %d', $index + 1),
                'searchable' => false,
                'activated_at' => null,
                'retired_at' => null,
            ];
        }

        return $segments;
    }

    public function indexSegments(
        Document $document,
        iterable $segments,
        ?RetrievalModelProfile $profile = null,
        string $collection = 'documents'
    ): int
    {
        $indexed = 0;

        foreach ($segments as $segment) {
            $content = $segment instanceof DocumentSegment ? $segment->content_text : $segment['content_text'];
            if (trim((string) $content) === '') {
                continue;
            }

            $embedding = $this->embeddingService->embed($content, $profile);
            if ($embedding === []) {
                continue;
            }

            $segmentId = $segment instanceof DocumentSegment ? $segment->id : ($segment['id'] ?? null);
            $segmentIndex = $segment instanceof DocumentSegment ? $segment->segment_index : $segment['segment_index'];
            $sourceLabel = $segment instanceof DocumentSegment ? $segment->source_label : $segment['source_label'];
            $pointId = $this->qdrantService->generatePointId('document', (int) $document->id, 'segment', (int) $segmentIndex);

            $this->qdrantService->upsert([
                [
                    'id' => $pointId,
                    'vector' => $embedding,
                    'payload' => [
                        'tenant_id' => (string) $document->tenant_id,
                        'document_id' => (string) $document->id,
                        'segment_id' => $segmentId ? (string) $segmentId : null,
                        'segment_index' => (int) $segmentIndex,
                        'chunk_preparation_run_id' => $segment instanceof DocumentSegment ? ($segment->chunk_preparation_run_id ? (string) $segment->chunk_preparation_run_id : null) : (($segment['chunk_preparation_run_id'] ?? null) ? (string) $segment['chunk_preparation_run_id'] : null),
                        'filename' => $document->filename,
                        'source_label' => $sourceLabel,
                        'content_text' => $content,
                        'embedding_model' => $this->embeddingService->getEmbeddingModel($profile),
                        'retrieval_model_profile' => $profile?->slug ?? 'default',
                        'retrieval_model_profile_id' => $profile?->id ? (string) $profile->id : null,
                        'chunking_profile_id' => $segment instanceof DocumentSegment ? ($segment->chunking_profile_id ? (string) $segment->chunking_profile_id : null) : (($segment['chunking_profile_id'] ?? null) ? (string) $segment['chunking_profile_id'] : null),
                        'embedding_dimensions' => $this->embeddingService->getEmbeddingDimensions($profile),
                        'token_count' => $segment instanceof DocumentSegment ? $segment->token_count : ($segment['token_count'] ?? null),
                        'document_status' => 'ready',
                    ],
                ],
            ], $collection);

            $indexed++;
        }

        return $indexed;
    }

    public function deleteDocumentVectors(Document $document, string $collection = 'documents'): void
    {
        $this->qdrantService->deleteByFilter([
            'must' => [
                [
                    'key' => 'document_id',
                    'match' => ['value' => (string) $document->id],
                ],
            ],
        ], $collection);
    }

    private function normalizeDocumentText(string $text): string
    {
        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $clean = str_replace(["\r\n", "\r"], "\n", $clean);
        $clean = preg_replace("/[ \t]+/", ' ', $clean) ?? $clean;
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean;
        $clean = preg_replace('/[\x{00AD}\x{200B}-\x{200D}\x{FEFF}]/u', '', $clean) ?? $clean;
        $clean = Str::of($clean)->trim()->toString();

        return $clean;
    }

    private function chunkTextWithOverlap(string $text, int $maxChars, int $overlap): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $maxChars, $length);
            $chunk = mb_substr($text, $start, $end - $start);

            if ($end < $length) {
                $breakpoint = mb_strrpos($chunk, "\n");
                if ($breakpoint !== false && $breakpoint > (int) ($maxChars * 0.6)) {
                    $chunk = mb_substr($chunk, 0, $breakpoint);
                    $end = $start + $breakpoint;
                }
            }

            $chunks[] = $chunk;
            $start = max($end - $overlap, $end === $length ? $length : 0);
        }

        return $chunks;
    }
}
