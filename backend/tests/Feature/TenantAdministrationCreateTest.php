<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantAdministrationCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_creates_a_tenant_with_an_initial_admin(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $slug = 'tenant-'.Str::lower(Str::random(8));
        $email = $slug.'@example.it';

        $response = $this->withToken($token)->postJson('/api/v1/tenants', [
            'tenant_name' => 'Tenant Demo',
            'tenant_slug' => $slug,
            'admin_full_name' => 'Admin Tenant Demo',
            'admin_email' => $email,
            'admin_role' => 'tenant-admin',
            'admin_access_methods' => ['company_account', 'password'],
            'admin_status' => 'active',
            'admin_mfa_policy' => 'optional',
            'admin_password' => 'Password12345',
        ]);

        $response->assertCreated()
            ->assertJsonPath('tenant.name', 'Tenant Demo')
            ->assertJsonPath('tenant.slug', $slug)
            ->assertJsonPath('initial_admin.email', $email)
            ->assertJsonPath('initial_admin.role', 'tenant-admin');

        $tenantId = (string) $response->json('tenant.id');
        $adminId = (string) $response->json('initial_admin.id');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'name' => 'Tenant Demo',
            'slug' => $slug,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $adminId,
            'tenant_id' => $tenantId,
            'email' => $email,
            'role' => 'tenant-admin',
        ]);

        $this->assertDatabaseHas('role_assignments', [
            'tenant_id' => $tenantId,
            'user_id' => $adminId,
            'role' => 'tenant-admin',
        ]);
    }

    #[Test]
    public function it_rejects_tenant_creation_without_an_initial_admin(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/tenants', [
                'tenant_name' => 'Tenant Invalido',
                'tenant_slug' => 'tenant-invalido',
                'admin_email' => 'admin-invalido@example.it',
                'admin_role' => 'tenant-admin',
                'admin_access_methods' => ['company_account'],
                'admin_status' => 'active',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
