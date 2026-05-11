<?php

namespace App\Services\Documents;

class PdfTextExtractorService
{
    public function extract(string $binary): string
    {
        $streams = $this->extractDecodedStreams($binary);
        $fragments = [];

        foreach ($streams as $stream) {
            $text = $this->extractTextFromStream($stream);
            if ($text !== '') {
                $fragments[] = $text;
            }
        }

        $combined = trim(implode("\n", $fragments));
        if ($combined !== '') {
            return $this->normalizeWhitespace($combined);
        }

        return $this->normalizeWhitespace($this->extractLiteralStrings($binary));
    }

    /**
     * @return list<string>
     */
    private function extractDecodedStreams(string $binary): array
    {
        $matches = [];
        preg_match_all('/<<(.*?)>>\s*stream\r?\n(.*?)\r?\nendstream/s', $binary, $matches, PREG_SET_ORDER);

        $streams = [];

        foreach ($matches as $match) {
            $dictionary = $match[1] ?? '';
            $stream = $match[2] ?? '';
            $streams[] = $this->decodeStream($stream, $dictionary);
        }

        return $streams;
    }

    private function decodeStream(string $stream, string $dictionary): string
    {
        if (! preg_match('/\/Filter\s*(\[[^\]]+\]|\/[A-Za-z0-9]+)/', $dictionary, $filterMatch)) {
            return $stream;
        }

        $filters = $filterMatch[1] ?? '';
        if (str_contains($filters, '/FlateDecode')) {
            $decoded = @zlib_decode($stream);
            if ($decoded !== false) {
                return $decoded;
            }

            $decoded = @gzuncompress($stream);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $stream;
    }

    private function extractTextFromStream(string $stream): string
    {
        $blocks = [];
        preg_match_all('/BT(.*?)ET/s', $stream, $matches);
        $candidateBlocks = $matches[1] ?? [];

        if ($candidateBlocks === []) {
            $candidateBlocks = [$stream];
        }

        foreach ($candidateBlocks as $block) {
            $parts = [];

            preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $block, $literalMatches);
            foreach ($literalMatches[0] as $operation) {
                if (preg_match('/(\((?:\\\\.|[^\\\\)])*\))\s*Tj/s', $operation, $textMatch)) {
                    $parts[] = $this->decodePdfLiteral($textMatch[1]);
                }
            }

            preg_match_all('/<([0-9A-Fa-f\s]+)>\s*Tj/s', $block, $hexMatches);
            foreach ($hexMatches[1] as $hex) {
                $parts[] = $this->decodePdfHex($hex);
            }

            preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $arrayMatches);
            foreach ($arrayMatches[1] as $arrayBody) {
                preg_match_all('/\((?:\\\\.|[^\\\\)])*\)|<([0-9A-Fa-f\s]+)>/s', $arrayBody, $tokens);

                foreach ($tokens[0] as $token) {
                    $parts[] = str_starts_with($token, '(')
                        ? $this->decodePdfLiteral($token)
                        : $this->decodePdfHex(trim($token, '<>'));
                }
            }

            $blockText = trim(implode(' ', array_filter($parts, fn (string $part): bool => trim($part) !== '')));
            if ($blockText !== '') {
                $blocks[] = $blockText;
            }
        }

        return implode("\n", $blocks);
    }

    private function extractLiteralStrings(string $binary): string
    {
        preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $binary, $matches);

        return implode("\n", array_map(
            fn (string $token): string => $this->decodePdfLiteral($token),
            $matches[0] ?? [],
        ));
    }

    private function decodePdfLiteral(string $literal): string
    {
        $value = substr($literal, 1, -1);
        $value = preg_replace_callback(
            '/\\\\([0-7]{1,3}|[nrtbf()\\\\])/',
            function (array $matches): string {
                return match ($matches[1]) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'b' => "\x08",
                    'f' => "\f",
                    '(' => '(',
                    ')' => ')',
                    '\\' => '\\',
                    default => chr(octdec($matches[1])),
                };
            },
            $value,
        ) ?? $value;

        $value = str_replace(["\\\n", "\\\r"], '', $value);

        return $this->decodeText($value);
    }

    private function decodePdfHex(string $hex): string
    {
        $normalized = preg_replace('/\s+/', '', $hex) ?? $hex;
        if ($normalized === '') {
            return '';
        }

        if (strlen($normalized) % 2 === 1) {
            $normalized .= '0';
        }

        $binary = hex2bin($normalized);
        if ($binary === false) {
            return '';
        }

        return $this->decodeText($binary);
    }

    private function decodeText(string $value): string
    {
        if (str_starts_with($value, "\xFE\xFF")) {
            return mb_convert_encoding(substr($value, 2), 'UTF-8', 'UTF-16BE');
        }

        if (str_starts_with($value, "\xFF\xFE")) {
            return mb_convert_encoding(substr($value, 2), 'UTF-8', 'UTF-16LE');
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
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
