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
            $text = $this->extractLiteralStrings($binary);

            if ($text === '') {
                throw new RuntimeException('Impossibile estrarre il testo dal PDF.', 0, $exception);
            }
        }

        $normalized = $this->normalizeWhitespace($text);

        if ($normalized !== '') {
            return $normalized;
        }

        $fallback = $this->normalizeWhitespace($this->extractLiteralStrings($binary));
        if ($fallback !== '') {
            return $fallback;
        }

        throw new RuntimeException('Impossibile estrarre il testo dal PDF.');
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

    private function extractLiteralStrings(string $binary): string
    {
        if (! preg_match_all('/\((?<text>(?:\\\\.|[^()])*)\)\s*Tj/', $binary, $matches)) {
            return '';
        }

        $parts = array_map(static function (string $value): string {
            return str_replace(
                ['\\(', '\\)', '\\\\'],
                ['(', ')', '\\'],
                $value,
            );
        }, $matches['text']);

        return implode("\n", $parts);
    }
}
