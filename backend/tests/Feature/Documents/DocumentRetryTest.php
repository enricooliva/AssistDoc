<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentRetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function operator_can_retry_a_failed_document(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        Storage::put('documents/'.$tenant->id.'/retry.txt', 'AssistDoc prepara chunk indicizzabili per il tenant.');

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'filename' => 'retry.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/retry.txt',
            'size_bytes' => 120,
            'status' => 'failed',
            'failure_reason' => 'Errore temporaneo',
            'uploaded_at' => now(),
            'last_status_at' => now(),
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/documents/'.$document->id.'/retry')
            ->assertAccepted()
            ->assertJsonPath('status', 'queued');
    }

    #[Test]
    public function retry_is_rejected_when_document_is_not_failed(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        $document = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'filename' => 'ready.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/ready.txt',
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/documents/'.$document->id.'/retry')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_DOCUMENT_STATE');
    }
}
