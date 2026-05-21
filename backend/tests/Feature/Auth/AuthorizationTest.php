<?php

namespace Tests\Feature\Auth;

use Illuminate\Http\UploadedFile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_denies_a_viewer_from_accessing_super_admin_routes(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/audit-events')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }

    #[Test]
    public function it_denies_a_viewer_from_listing_enterprise_users(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/users')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }

    #[Test]
    public function it_denies_a_viewer_from_uploading_documents(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->post('/api/v1/documents', [
                'file' => UploadedFile::fake()->createWithContent('viewer.txt', 'Contenuto riservato'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }

    #[Test]
    public function it_denies_a_viewer_from_deleting_documents(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->deleteJson('/api/v1/documents/1')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }

    #[Test]
    public function it_allows_a_viewer_to_access_chat_routes(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations')
            ->assertOk();
    }
}
