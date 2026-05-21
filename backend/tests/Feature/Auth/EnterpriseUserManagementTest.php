<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnterpriseUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_super_admin_can_list_provisioned_users(): void
    {
        $token = $this->login('admin@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonStructure(['items', 'page', 'perPage', 'total']);
    }

    #[Test]
    public function a_super_admin_can_provision_and_unlock_a_user(): void
    {
        $token = $this->login('admin@assistdoc.local');
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/users', [
                'full_name' => 'Mario Rossi',
                'email' => 'mario.rossi@example.test',
                'tenant_id' => (string) $tenant->id,
                'role' => 'viewer',
                'access_methods' => ['password'],
                'status' => 'active',
                'mfa_policy' => 'optional',
                'password' => 'Password12345',
            ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'mario.rossi@example.test');

        $userId = (string) $createResponse->json('user.id');

        User::query()->whereKey($userId)->update([
            'status' => 'locked',
            'locked_at' => now(),
            'locked_until' => now()->addMinutes(15),
            'lockout_reason' => 'password_failures',
        ]);

        $this->withToken($token)
            ->postJson(sprintf('/api/v1/users/%s/unlock', $userId))
            ->assertOk()
            ->assertJsonPath('user.status', 'active');
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
