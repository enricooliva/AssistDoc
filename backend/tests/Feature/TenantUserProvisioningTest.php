<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantUserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_adds_a_user_to_an_existing_tenant(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'tenant-admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $email = 'nuovo-'.Str::lower(Str::random(8)).'@example.it';

        $response = $this->withToken($token)->postJson("/api/v1/tenants/{$tenant->id}/users", [
            'full_name' => 'Nuovo Utente',
            'email' => $email,
            'role' => 'viewer',
            'access_methods' => ['password'],
            'status' => 'active',
            'mfa_policy' => 'optional',
            'password' => 'Password12345',
        ]);

        $response->assertCreated()
            ->assertJsonPath('tenant.id', (string) $tenant->id)
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('user.role', 'viewer');

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => 'viewer',
        ]);
    }

    #[Test]
    public function it_rejects_user_creation_for_an_inactive_tenant(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Inattivo',
            'slug' => 'tenant-inattivo',
            'status' => 'inactive',
        ]);

        $this->withToken($token)->postJson("/api/v1/tenants/{$tenant->id}/users", [
            'full_name' => 'Utente Bloccato',
            'email' => 'utente-bloccato@example.it',
            'role' => 'viewer',
            'access_methods' => ['company_account'],
            'status' => 'active',
            'mfa_policy' => 'optional',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'TENANT_INACTIVE');
    }
}
