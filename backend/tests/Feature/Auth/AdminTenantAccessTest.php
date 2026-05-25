<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\EnterpriseUserAccessFixtures;
use Tests\TestCase;

class AdminTenantAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function tenant_admin_can_use_chat_documents_and_uploads(): void
    {
        $token = EnterpriseUserAccessFixtures::login($this, 'tenant-admin@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations')
            ->assertOk()
            ->assertJsonStructure(['items', 'page', 'perPage', 'total']);

        $this->withToken($token)
            ->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonStructure(['items', 'page', 'perPage', 'total']);

        $this->withToken($token)
            ->getJson('/api/v1/rag/chunking-profiles')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->withToken($token)
            ->post('/api/v1/documents', [
                'file' => UploadedFile::fake()->createWithContent('tenant-admin.txt', 'Documento riservato al tenant.'),
                'tags' => ['tenant-admin', 'documenti'],
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('filename', 'tenant-admin.txt');
    }

    #[Test]
    public function super_admin_can_use_chat_and_documents_as_before(): void
    {
        $token = EnterpriseUserAccessFixtures::login($this, 'admin@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/documents')
            ->assertOk();
    }

}
