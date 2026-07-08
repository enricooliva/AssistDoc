<?php

namespace App\Services;

use App\Attachment;
use App\Contratto;
use App\ServicesEmbeddingService;
use App\Services\QdrantService;
use App\Services\PdfSignService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentIndexerService
{
    protected $logger;

    public function __construct(
        protected EmbeddingService $embeddingService,
        protected QdrantService $qdrantService,
        callable $logger = null
    ) {
        $this->logger = $logger;
    }

    /**
     * Log a message if a logger is provided
     */
    protected function info(string $message)
    {
        if (is_callable($this->logger)) {
            ($this->logger)($message);
        }
    }

    /**
     * Index all chunks of a single attachment
     */
    public function index(Attachment $attach, Contratto $contratto, string $collection = 'attachments'): void
    {
        if (in_array($attach->attachmenttype_codice, ['CODICE_COMPORTAMENTO_COMPLETATO','TRASPARENZA_COMPLETATO'])) {
            return;
        }

        // Check if this chunk is already stored in Qdrant
        $existing = $this->qdrantService->payloadExists('attachment_id', $attach->id);
        if ($existing) {
            $this->info("Attachment {$attach->id} already embedded. Skipping.");
            return;
        }

        $pdfData = Storage::get($attach->filepath);
        $text = PdfSignService::extractContentWithoutSignature($pdfData);
       
        if (empty(trim($text))) {
            $this->info("Attachment ID {$attach->id} is empty or unreadable");
            return;
        }

        $text = $this->cleanTextUtf8($text);
        $normalizedText = $this->normalizeDocumentText($text);

        $this->indexChunks($normalizedText, $attach, $contratto, $collection);
    }

    /**
     * Delete an attachment from Qdrant
     */
    public function delete(Attachment $attachment, string $collection = null): void
    {
        $attachmentId = $attachment->id;
        // Costruisci un filtro per tutti i punti con attachment_id = $attachmentId
        $filter = [
            'must' => [            
                [
                    'key' => 'attachment_id',
                    'match' => [
                        'value' => $attachmentId
                    ]
                ]
            ]
        ];
    
        // Esegui la cancellazione
        $this->qdrantService->deleteByFilter($filter);

        // Assuming chunks were generated with levels and indexes
        // $levels = ['chunk_800','chunk_1600','chunk_3200'];
        // foreach ($levels as $level) {
        //     for ($i = 0; $i < 1000; $i++) { // max 1000 chunks per level (adjust if needed)
        //         $pointId = $this->generatePointId($attach->id, $level, $i);
        //         $this->qdrantService->deletePoint($pointId, $collection);
        //     }
        // }
    }

    protected function indexChunks(string $text, Attachment $attach, Contratto $ctr, string $collection): void
    {
        $chunkConfigs = [
            'chunk_800'  => ['maxChars' => 800,  'overlap' => 200],
            'chunk_1600' => ['maxChars' => 1600, 'overlap' => 400],
            'chunk_3200' => ['maxChars' => 3200, 'overlap' => 800],
        ];

        foreach ($chunkConfigs as $level => $config) {
            $chunks = $this->chunkTextAndNormalize($text, $config['maxChars'], $config['overlap']);
            foreach ($chunks as $i => $chunk) {

                $pointId = $this->generatePointId($attach->id, $level, $i);              

                // // Check if this chunk is already stored in Qdrant
                // $existing = $this->qdrantService->getPoint($pointId);
                // if ($existing) {
                //     $this->info("Attachment chunk {$i} for attachment {$attach->id} already embedded. Skipping.");
                //     continue;
                // }

                $embedding = $this->embeddingService->generateEmbedding($chunk);
               
                if (empty($embedding)) {
                    $this->warn("Empty embedding for {$level} chunk {$i} of attachment {$attach->id}");
                    continue;
                }

                $payload = $this->qdrantService->buildPayload('contratto_attachment', $ctr, [                    
                    'attachment_id' => $attach->id,
                    'attachmenttype_codice' => $attach->attachmenttype_codice ?? null,
                    'filename' => $attach->filename,
                    'mime' => $attach->mime,
                    'level' => $level,
                    'chunk' => $i,
                    'text' => $chunk,
                    'embedding_model' => $this->embeddingService->getEmbeddingModel(),
                ]);

                $this->qdrantService->upsert([
                    [
                        'id' => $pointId,
                        'vector' => $embedding,
                        'payload' => $payload,
                    ]
                ], $collection);

                $this->info("Indexed {$level} chunks for attachment {$attach->id}: " . $i);
            }
        }
    }

    protected function generatePointId(int $attachId, string $level = '', int $chunkIndex = 0): int
    {

        return $this->qdrantService->generatePointId('attachment', $attachId, $level, $chunkIndex);
        // $base = 3_000_000_000;

        // $levelOffset = match($level) {
        //     'chunk_800' => 0,
        //     'chunk_1600' => 100_000,
        //     'chunk_3200' => 200_000,
        //     default => 0,
        // };

        // return $base + ($attachId * 1_000) + $levelOffset + $chunkIndex;
    }

    protected function cleanTextUtf8(string $text): string
    {
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    protected function normalizeDocumentText(string $text): string
    {
        // Basic normalization: remove extra spaces, line breaks, form feed, etc.
        $clean = preg_replace("/[ \t]+/", ' ', $text);
        $clean = preg_replace("/\r\n|\r|\n/", "\n", $clean);
        $clean = preg_replace('/\n+/', "\n", $clean);
        $clean = preg_replace("/\b(TESTTEST|TEST|ANTEPRIMA)\b/i", '', $clean);
        $clean = preg_replace("/\f/", "\n", $clean);
        $clean = implode("\n", array_map('trim', explode("\n", $clean)));
        $clean = preg_replace("/\n{2,}/", "\n\n", trim($clean));
        $clean = preg_replace('/[\x{00AD}\x{200B}-\x{200D}\x{FEFF}]/u', '', $clean);
       
        $clean = Str::before($clean, 'INFORMATIVA SUL TRATTAMENTO DEI DATI PERSONALI');
        $clean = Str::before($clean, 'INFORMATIVA SUL TRATTAMENTO DE I DATI PERSONALI');

        return $clean;
    }

    /**
     * Clean and split a contract text into chunks of ≤1800 chars with 300-char overlap.
     * Short chunks are merged into the previous one.
     */
    public function chunkTextAndNormalize(string $text, int $maxChars = 800, int $overlap = 200): array
    {             
        // 2️Split text by size with overlap
        $chunks = $this->chunkTextWithOverlap($text, $maxChars, $overlap);
        
        // 3️Merge short chunks into the previous one
        $merged = [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') continue;

            $length = mb_strlen($chunk);
            if ($length < $maxChars - 1000 && !empty($merged)) {
                $merged[count($merged) - 1] .= ' ' . $chunk;
            } else {
                $merged[] = $chunk;
            }
        }

        return $merged;
    }

      /**
     * Split text into chunks of maxChars with overlap.
     */
    protected function chunkTextWithOverlap(string $text, int $maxChars, int $overlap): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $maxChars, $length);
            $chunk = mb_substr($text, $start, $end - $start);

            // Try to end at a sentence boundary for cleaner chunks
            if ($end < $length) {
                $nextDot = mb_strpos($text, '.', $end - 100);
                if ($nextDot !== false && $nextDot - $start <= $maxChars + 100) {
                    $chunk = mb_substr($text, $start, $nextDot - $start + 1);
                    $end = $nextDot + 1;
                }
            }

            $chunks[] = trim($chunk);
            $start = $end;
            if ($end < $length) {
                $start = $end - $overlap;
            }
            if ($start < 0) $start = 0;
        }

        return $chunks;
    }


}
