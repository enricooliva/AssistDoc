<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantAdministrationUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_super_admin_can_update_an_existing_tenant(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $tenant = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();

        $this->withToken($token)
            ->patchJson("/api/v1/tenants/{$tenant->id}", [
                'tenant_name' => 'Tenant B Aggiornato',
                'tenant_slug' => 'tenant-b-aggiornato',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('tenant.id', (string) $tenant->id)
            ->assertJsonPath('tenant.name', 'Tenant B Aggiornato')
            ->assertJsonPath('tenant.slug', 'tenant-b-aggiornato')
            ->assertJsonPath('tenant.status', 'inactive');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Tenant B Aggiornato',
            'slug' => 'tenant-b-aggiornato',
            'status' => 'inactive',
        ]);
    }

    #[Test]
    public function it_rejects_a_duplicate_tenant_slug_during_update(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $tenant = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();

        $this->withToken($token)
            ->patchJson("/api/v1/tenants/{$tenant->id}", [
                'tenant_name' => 'Tenant B',
                'tenant_slug' => 'tenant-c',
                'status' => 'active',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.fieldErrors.tenant_slug.0', 'The tenant slug has already been taken.');
    }
}
