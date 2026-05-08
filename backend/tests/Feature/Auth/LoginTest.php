<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_authenticates_a_valid_local_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'full_name',
                    'email',
                    'role',
                    'tenant' => ['id', 'name', 'slug'],
                ],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'viewer@assistdoc.local')
            ->assertJsonPath('user.role', 'viewer')
            ->assertJsonPath('user.tenant.slug', 'assistdoc-demo');
    }

    #[Test]
    public function it_returns_validation_errors_for_missing_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details' => ['email', 'password']],
            ]);
    }

    #[Test]
    public function it_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
