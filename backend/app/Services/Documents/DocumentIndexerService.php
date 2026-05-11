<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSegment;
use App\Services\AI\EmbeddingService;
use App\Services\QdrantService;
use Illuminate\Support\Str;

class DocumentIndexerService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly QdrantService $qdrantService,
    ) {
    }

    public function buildSegments(Document $document, string $text): array
    {
        $normalizedText = $this->normalizeDocumentText($text);
        $chunks = $this->chunkTextWithOverlap($normalizedText, 1200, 180);
        $segments = [];

        foreach ($chunks as $index => $chunk) {
            $trimmed = trim($chunk);
            if ($trimmed === '') {
                continue;
            }

            $segments[] = [
                'tenant_id' => $document->tenant_id,
                'document_id' => $document->id,
                'segment_index' => $index,
                'content_text' => $trimmed,
                'source_label' => sprintf('Segmento %d', $index + 1),
                'searchable' => false,
            ];
        }

        return $segments;
    }

    public function indexSegments(Document $document, iterable $segments, string $collection = 'documents'): int
    {
        $indexed = 0;

        foreach ($segments as $segment) {
            $content = $segment instanceof DocumentSegment ? $segment->content_text : $segment['content_text'];
            if (trim((string) $content) === '') {
                continue;
            }

            $embedding = $this->embeddingService->embed($content);
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
                        'filename' => $document->filename,
                        'source_label' => $sourceLabel,
                        'content_text' => $content,
                        'embedding_model' => $this->embeddingService->getEmbeddingModel(),
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
