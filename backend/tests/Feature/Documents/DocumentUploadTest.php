<?php

namespace Tests\Feature\Documents;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function operator_can_upload_a_document_and_trigger_ingestion(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $file = UploadedFile::fake()->createWithContent(
            'manuale.txt',
            'AssistDoc usa isolamento tenant lato server e rende i documenti ricercabili.'
        );

        $response = $this->withToken($token)
            ->post('/api/v1/documents', [
                'file' => $file,
                'tags' => ['manuale', 'tenant', 'manuale'],
            ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('filename', 'manuale.txt')
            ->assertJsonPath('sourceType', 'file')
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('activeChunkingProfile.name', 'Medium')
            ->assertJsonPath('tags.0', 'manuale')
            ->assertJsonPath('tags.1', 'tenant');

        $documentId = (string) $response->json('id');

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'filename' => 'manuale.txt',
        ]);

        $this->assertDatabaseCount('document_segments', 1);
    }

    #[Test]
    public function upload_requires_a_file(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->post('/api/v1/documents', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    #[Test]
    public function upload_validates_each_tag_as_a_short_string(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->post('/api/v1/documents', [
                'file' => UploadedFile::fake()->createWithContent('manuale.txt', 'contenuto'),
                'tags' => [str_repeat('a', 51)],
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    #[Test]
    public function operator_can_upload_a_pdf_and_trigger_text_extraction_plus_embedding(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $file = UploadedFile::fake()->createWithContent(
            'manuale.pdf',
            $this->buildPdf('AssistDoc estrae il contenuto dai PDF caricati prima di indicizzarli.')
        );

        $response = $this->withToken($token)
            ->post('/api/v1/documents', ['file' => $file], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('filename', 'manuale.pdf')
            ->assertJsonPath('sourceType', 'file')
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('activeChunkingProfile.name', 'Medium');

        $documentId = (string) $response->json('id');

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'filename' => 'manuale.pdf',
            'status' => 'ready',
        ]);
        $this->assertDatabaseHas('document_segments', [
            'document_id' => $documentId,
            'searchable' => true,
            'content_text' => 'AssistDoc estrae il contenuto dai PDF caricati prima di indicizzarli.',
        ]);
        $this->assertDatabaseCount('document_segments', 1);
    }

    #[Test]
    public function operator_can_submit_direct_text_and_trigger_ingestion(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $response = $this->withToken($token)
            ->postJson('/api/v1/documents/text', [
                'sourceLabel' => 'Procedura interna',
                'text' => 'AssistDoc tratta il testo incollato come contenuto ricercabile del tenant.',
                'tags' => ['procedura', 'tenant'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('filename', 'Procedura interna')
            ->assertJsonPath('sourceType', 'text')
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('mediaType', 'text/plain');

        $documentId = (string) $response->json('id');

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'filename' => 'Procedura interna',
            'source_type' => 'text',
            'media_type' => 'text/plain',
        ]);
        $this->assertDatabaseHas('document_segments', [
            'document_id' => $documentId,
            'searchable' => true,
            'content_text' => 'AssistDoc tratta il testo incollato come contenuto ricercabile del tenant.',
        ]);
    }

    #[Test]
    public function direct_text_requires_non_empty_content(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/documents/text', [
                'sourceLabel' => 'Vuoto',
                'text' => '   ',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    #[Test]
    public function direct_text_is_limited_by_the_configured_max_input_length(): void
    {
        config()->set('rag.text_ingestion_max_input_chars', 10);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/documents/text', [
                'sourceLabel' => 'Troppo lungo',
                'text' => '12345678901',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
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
