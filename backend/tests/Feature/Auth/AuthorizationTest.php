<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
}
