<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnterpriseUserManagementSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_super_admin_can_search_users_by_email_and_name(): void
    {
        $token = $this->login('admin@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/users?query=Viewer Demo')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.email', 'viewer@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/users?query=operator@assistdoc.local')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.email', 'operator@assistdoc.local');
    }

    #[Test]
    public function searching_with_no_matches_returns_an_empty_list(): void
    {
        $token = $this->login('admin@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/users?query=nessun-risultato')
            ->assertOk()
            ->assertJsonCount(0, 'items')
            ->assertJsonPath('total', 0);
    }

    #[Test]
    public function search_results_remain_tenant_scoped(): void
    {
        $token = $this->login('admin@assistdoc.local');
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();

        $response = $this->withToken($token)
            ->getJson('/api/v1/users?query=viewer')
            ->assertOk();

        $emails = collect($response->json('items'))->pluck('email');

        $this->assertFalse($emails->contains('viewer-b@assistdoc.local'));
        $this->assertTrue(
            collect($response->json('items'))->every(fn (array $item): bool => $item['tenant']['id'] === (string) $tenantB->id ? false : true)
        );
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
