<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_lists_tenants_with_membership_summary(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $slug = 'tenant-'.Str::lower(Str::random(8));
        $this->withToken($token)->postJson('/api/v1/tenants', [
            'tenant_name' => 'Tenant Overview',
            'tenant_slug' => $slug,
            'admin_full_name' => 'Admin Overview',
            'admin_email' => $slug.'@example.it',
            'admin_role' => 'tenant-admin',
            'admin_access_methods' => ['company_account'],
            'admin_status' => 'active',
            'admin_mfa_policy' => 'optional',
        ])->assertCreated();

        $this->withToken($token)
            ->getJson('/api/v1/tenants')
            ->assertOk()
            ->assertJsonPath('items.0.slug', 'assistdoc-demo');

        $response = $this->withToken($token)->getJson('/api/v1/tenants');
        $item = collect($response->json('items'))->firstWhere('slug', $slug);

        $this->assertNotNull($item);
        $this->assertSame('Tenant Overview', $item['name']);
        $this->assertSame(1, $item['member_count']);
        $this->assertSame(1, $item['admin_count']);
    }

    #[Test]
    public function it_shows_tenant_members_in_the_detail_view(): void
    {
        $token = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@assistdoc.local',
            'password' => 'password123',
        ])->json('token');

        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();

        $this->withToken($token)
            ->getJson("/api/v1/tenants/{$tenant->id}")
            ->assertOk()
            ->assertJsonPath('tenant.slug', 'assistdoc-demo');

        $this->assertNotEmpty(collect($this->withToken($token)->getJson("/api/v1/tenants/{$tenant->id}")->json('members'))->all());
    }
}
