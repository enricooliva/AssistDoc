<?php

namespace Tests\Unit\Documents;

use App\Models\ChunkingProfile;
use App\Models\Document;
use App\Models\DocumentSegment;
use App\Models\RetrievalModelProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Documents\DocumentProcessingService;
use App\Services\QdrantService;
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
            'source_type' => 'file',
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
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $tenant->id,
            'event_type' => 'document.indexed',
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
            'source_type' => 'file',
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
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $tenant->id,
            'event_type' => 'document.index_failed',
        ]);
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
            'source_type' => 'file',
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
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $tenant->id,
            'event_type' => 'document.indexed',
        ]);
    }

    #[Test]
    public function it_withdraws_previous_vectors_and_records_validation_failures_when_chunking_is_invalid(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        Storage::put('documents/'.$tenant->id.'/manuale.txt', str_repeat('qwen ', 1000));

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'file',
            'filename' => 'manuale.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/manuale.txt',
            'size_bytes' => 120,
            'status' => 'queued',
            'uploaded_at' => now(),
            'last_status_at' => now(),
        ]);

        $invalid = ChunkingProfile::query()->create([
            'name' => 'Troppo Grande',
            'slug' => 'troppo-grande',
            'chunk_size_tokens' => 50000,
            'overlap_tokens' => 10,
            'active' => true,
        ]);

        $result = app(DocumentProcessingService::class)->process(
            (string) $tenant->id,
            (string) $document->id,
            (string) $invalid->id,
            (string) $operator->id,
        );

        $this->assertSame('failed', $result['status']);
        $this->assertDatabaseHas('chunk_preparation_runs', [
            'tenant_id' => $tenant->id,
            'document_id' => $document->id,
            'retrieval_model_profile_id' => \App\Models\RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail()->id,
            'chunking_profile_id' => $invalid->id,
            'status' => 'failed',
            'failure_code' => 'PREPARATION_FAILED',
        ]);
        $this->assertDatabaseHas('preparation_validation_failures', [
            'document_id' => $document->id,
            'failure_code' => 'PREPARATION_FAILED',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $tenant->id,
            'event_type' => 'document.index_failed',
        ]);
    }

    #[Test]
    public function it_preserves_segments_from_other_chunking_profiles_when_reprocessing_a_document(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        $profile = RetrievalModelProfile::query()->where('slug', config('rag.default_retrieval_profile.slug'))->firstOrFail();
        $small = ChunkingProfile::query()->where('slug', 'small')->firstOrFail();
        $large = ChunkingProfile::query()->where('slug', 'large')->firstOrFail();
        Storage::put('documents/'.$tenant->id.'/manuale.txt', str_repeat('AssistDoc organizza segmenti. ', 50));

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'file',
            'filename' => 'manuale.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/manuale.txt',
            'size_bytes' => 120,
            'status' => 'queued',
            'uploaded_at' => now(),
            'last_status_at' => now(),
        ]);

        $service = app(DocumentProcessingService::class);
        $service->process((string) $tenant->id, (string) $document->id, (string) $small->id, (string) $operator->id);
        $service->process((string) $tenant->id, (string) $document->id, (string) $large->id, (string) $operator->id);

        $this->assertGreaterThan(0, DocumentSegment::query()
            ->where('document_id', $document->id)
            ->where('retrieval_model_profile_id', $profile->id)
            ->where('chunking_profile_id', $small->id)
            ->whereNull('retired_at')
            ->count());
        $this->assertGreaterThan(0, DocumentSegment::query()
            ->where('document_id', $document->id)
            ->where('retrieval_model_profile_id', $profile->id)
            ->where('chunking_profile_id', $large->id)
            ->whereNull('retired_at')
            ->count());
    }

    #[Test]
    public function it_processes_direct_text_documents_stored_as_private_plain_text_artifacts(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        Storage::put('documents/'.$tenant->id.'/procedura.txt', 'AssistDoc indicizza anche il testo incollato.');

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'source_type' => 'text',
            'filename' => 'Procedura interna',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/procedura.txt',
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
            'content_text' => 'AssistDoc indicizza anche il testo incollato.',
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
