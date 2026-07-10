<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantAdministrationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_denies_a_viewer_from_accessing_tenant_administration_routes(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/tenants')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');

        $this->withToken($token)
            ->postJson('/api/v1/tenants', [
                'tenant_name' => 'Tenant Negato',
                'tenant_slug' => 'tenant-negato',
                'admin_full_name' => 'Admin Negato',
                'admin_email' => 'admin-negato@example.it',
                'admin_role' => 'tenant-admin',
                'admin_access_methods' => ['company_account'],
                'admin_status' => 'active',
                'admin_mfa_policy' => 'optional',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');

        $this->withToken($token)
            ->patchJson('/api/v1/tenants/1', [
                'tenant_name' => 'Tenant Negato',
                'tenant_slug' => 'tenant-negato',
                'status' => 'inactive',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }
}
