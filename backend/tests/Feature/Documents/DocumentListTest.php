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

class DocumentListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function operator_can_browse_a_paginated_tenant_document_list(): void
    {
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();

        foreach (range(1, 26) as $index) {
            Document::query()->create([
                'tenant_id' => $tenant->id,
                'uploaded_by_user_id' => $index % 2 === 0 ? $viewer->id : $operator->id,
                'filename' => sprintf('documento-%02d.txt', $index),
                'media_type' => 'text/plain',
                'storage_path' => sprintf('documents/%s/documento-%02d.txt', $tenant->id, $index),
                'tags' => $index === 1 ? ['manuale', 'tenant'] : ['tenant'],
                'size_bytes' => 120 + $index,
                'status' => 'ready',
                'uploaded_at' => now()->subMinutes($index),
                'last_status_at' => now()->subMinutes($index),
                'indexed_at' => now()->subMinutes($index),
            ]);
        }

        $softDeleted = Document::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by_user_id' => $operator->id,
            'filename' => 'eliminato.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenant->id.'/eliminato.txt',
            'tags' => ['archivio'],
            'size_bytes' => 200,
            'status' => 'deleted',
            'uploaded_at' => now()->subDay(),
            'last_status_at' => now()->subDay(),
            'indexed_at' => now()->subDay(),
            'deleted_at' => now()->subHour(),
            'deleted_by_user_id' => $operator->id,
        ]);
        $softDeleted->save();

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $firstPage = $this->withToken($token)
            ->getJson('/api/v1/documents?page=1&perPage=25')
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('perPage', 25)
            ->assertJsonPath('total', 26)
            ->assertJsonCount(25, 'items');

        $firstPage->assertJsonPath('items.0.filename', 'documento-01.txt');
        $firstPage->assertJsonPath('items.0.tags.0', 'manuale');
        $firstPage->assertJsonPath('items.0.uploadedBy.fullName', 'Operator Demo');

        $secondPage = $this->withToken($token)
            ->getJson('/api/v1/documents?page=2&perPage=25')
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonPath('perPage', 25)
            ->assertJsonPath('total', 26)
            ->assertJsonCount(1, 'items');

        $secondPage->assertJsonPath('items.0.filename', 'documento-26.txt');
        $secondPage->assertJsonMissing(['filename' => 'eliminato.txt']);
    }

    #[Test]
    public function document_list_validation_rejects_invalid_pagination(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/documents?page=0&perPage=0')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    #[Test]
    public function document_list_is_scoped_to_the_current_tenant(): void
    {
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();
        $viewerB = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail();

        Document::query()->create([
            'tenant_id' => $tenantB->id,
            'uploaded_by_user_id' => $viewerB->id,
            'filename' => 'tenant-b.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'documents/'.$tenantB->id.'/tenant-b.txt',
            'tags' => ['tenant-b'],
            'size_bytes' => 120,
            'status' => 'ready',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $response = $this->withToken($token)
            ->getJson('/api/v1/documents')
            ->assertOk();

        $this->assertSame(0, collect($response->json('items'))->where('filename', 'tenant-b.txt')->count());
    }
}
