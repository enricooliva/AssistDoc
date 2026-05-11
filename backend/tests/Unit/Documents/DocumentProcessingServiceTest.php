<?php

namespace Tests\Unit\Documents;

use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Documents\DocumentProcessingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_marks_a_document_ready_when_text_can_be_segmented_and_indexed(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        Storage::put('documents/'.$tenant->id.'/manuale.txt', 'AssistDoc indicizza i documenti del tenant.');

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'filename' => 'manuale.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/manuale.txt',
            'size_bytes' => 120,
            'status' => 'queued',
            'uploaded_at' => now(),
            'last_status_at' => now(),
        ]);

        $result = app(DocumentProcessingService::class)->process((string) $tenant->id, (string) $document->id);

        $this->assertSame('ready', $result['status']);
        $this->assertDatabaseHas('document_segments', [
            'document_id' => $document->id,
            'searchable' => true,
        ]);
    }

    #[Test]
    public function it_marks_a_document_failed_when_no_text_is_extractable(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        Storage::put('documents/'.$tenant->id.'/vuoto.txt', '');

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'filename' => 'vuoto.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/vuoto.txt',
            'size_bytes' => 1,
            'status' => 'queued',
            'uploaded_at' => now(),
            'last_status_at' => now(),
        ]);

        $result = app(DocumentProcessingService::class)->process((string) $tenant->id, (string) $document->id);

        $this->assertSame('failed', $result['status']);
    }

    #[Test]
    public function it_extracts_pdf_text_before_creating_embeddings(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        Storage::put(
            'documents/'.$tenant->id.'/manuale.pdf',
            $this->buildPdf('AssistDoc estrae il testo dal PDF e genera embedding ricercabili.')
        );

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'filename' => 'manuale.pdf',
            'media_type' => 'application/pdf',
            'storage_path' => 'documents/'.$tenant->id.'/manuale.pdf',
            'size_bytes' => 512,
            'status' => 'queued',
            'uploaded_at' => now(),
            'last_status_at' => now(),
        ]);

        $result = app(DocumentProcessingService::class)->process((string) $tenant->id, (string) $document->id);

        $this->assertSame('ready', $result['status']);
        $this->assertDatabaseHas('document_segments', [
            'document_id' => $document->id,
            'searchable' => true,
        ]);
        $this->assertDatabaseHas('document_segments', [
            'document_id' => $document->id,
            'content_text' => 'AssistDoc estrae il testo dal PDF e genera embedding ricercabili.',
        ]);
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
