<?php

namespace Tests\Unit\Chat;

use App\Services\Chat\CitationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CitationServiceTest extends TestCase
{
    #[Test]
    public function it_maps_unique_search_results_into_visible_citations(): void
    {
        $service = new CitationService();

        $citations = $service->fromSearchResults([
            [
                'documentId' => '1',
                'documentSegmentId' => '10',
                'documentName' => 'Manuale.pdf',
                'sourceLabel' => 'Segmento 1',
                'quoteText' => 'Testo utile',
            ],
            [
                'documentId' => '1',
                'documentSegmentId' => '10',
                'documentName' => 'Manuale.pdf',
                'sourceLabel' => 'Segmento 1',
                'quoteText' => 'Duplicato',
            ],
        ]);

        $this->assertCount(1, $citations);
        $this->assertSame('Manuale.pdf', $citations[0]['documentName']);
    }
}
