<?php

namespace Tests\Unit\AI;

use App\Services\AI\ChatCompletionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatCompletionServiceTest extends TestCase
{
    #[Test]
    public function it_builds_an_answer_from_the_top_grounding_results(): void
    {
        $service = new ChatCompletionService();

        $response = $service->answer('Come funziona AssistDoc?', [
            [
                'documentName' => 'Manuale Tenant.pdf',
                'quoteText' => 'AssistDoc applica isolamento tenant lato server.',
            ],
        ]);

        $this->assertSame('answered', $response['responseState']);
        $this->assertStringContainsString('Manuale Tenant.pdf', $response['body']);
    }

    #[Test]
    public function it_throws_when_no_grounding_results_are_available(): void
    {
        $service = new ChatCompletionService();

        $this->expectException(\RuntimeException::class);

        $service->answer('Domanda vuota', []);
    }
}
