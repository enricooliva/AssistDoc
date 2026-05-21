<?php

namespace App\Services\Documents;

use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfTextExtractorService
{
    public function extract(string $binary): string
    {
        try {
            $pdf = (new Parser())->parseContent($binary);
            $text = $pdf->getText();
        } catch (\Throwable $exception) {
            throw new RuntimeException('Impossibile estrarre il testo dal PDF.', 0, $exception);
        }

        return $this->normalizeWhitespace($text);
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[^\P{C}\n\t]+/u', '', $text) ?? $text;
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
