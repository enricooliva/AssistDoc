<?php

namespace Tests\Unit\Documents;

use App\Services\Documents\PdfTextExtractorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfTextExtractorServiceTest extends TestCase
{
    #[Test]
    public function it_extracts_literal_text_from_a_pdf_content_stream(): void
    {
        $pdf = $this->buildPdf('AssistDoc indicizza il contenuto PDF del tenant.');

        $text = app(PdfTextExtractorService::class)->extract($pdf);

        $this->assertStringContainsString('AssistDoc indicizza il contenuto PDF del tenant.', $text);
    }

    private function buildPdf(string $text): string
    {
        $escapedText = str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text,
        );

        $stream = "BT\n/F1 12 Tf\n72 720 Td\n({$escapedText}) Tj\nET";
        $length = strlen($stream);

        return <<<PDF
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>
endobj
4 0 obj
<< /Length {$length} >>
stream
{$stream}
endstream
endobj
trailer
<< /Root 1 0 R >>
%%EOF
PDF;
    }
}
