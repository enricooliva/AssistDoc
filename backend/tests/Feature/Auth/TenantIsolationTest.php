<?php

namespace Tests\Feature\Auth;

use App\Models\Document;
use App\Models\ChatConversation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_does_not_expose_documents_from_another_tenant(): void
    {
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();
        $userB = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail();
        $foreignDocument = Document::query()->create([
            'tenant_id' => $tenantB->id,
            'uploaded_by_user_id' => $userB->id,
            'filename' => 'tenant-b.pdf',
            'media_type' => 'application/pdf',
            'storage_path' => 'private/tenant-b.pdf',
            'size_bytes' => 1000,
            'status' => 'indexed',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $viewerToken = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($viewerToken)
            ->getJson('/api/v1/documents/'.$foreignDocument->id)
            ->assertStatus(404);
    }

    #[Test]
    public function it_does_not_include_documents_from_another_tenant_in_the_browse_list(): void
    {
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();
        $userB = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail();
        $foreignDocument = Document::query()->create([
            'tenant_id' => $tenantB->id,
            'uploaded_by_user_id' => $userB->id,
            'filename' => 'tenant-b-list.txt',
            'media_type' => 'text/plain',
            'storage_path' => 'private/tenant-b-list.txt',
            'size_bytes' => 1000,
            'status' => 'indexed',
            'uploaded_at' => now(),
            'last_status_at' => now(),
            'indexed_at' => now(),
        ]);

        $viewerToken = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $response = $this->withToken($viewerToken)
            ->getJson('/api/v1/documents')
            ->assertOk();

        $this->assertSame(0, collect($response->json('items'))->where('filename', $foreignDocument->filename)->count());
    }

    #[Test]
    public function it_does_not_expose_chat_conversations_from_another_tenant(): void
    {
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();
        $userB = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail();
        $foreignConversation = ChatConversation::query()->create([
            'tenant_id' => $tenantB->id,
            'user_id' => $userB->id,
            'title' => 'Chat tenant B',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $viewerToken = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($viewerToken)
            ->getJson('/api/v1/chat/conversations/'.$foreignConversation->id)
            ->assertStatus(404);
    }
}
