<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnterpriseUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_super_admin_can_soft_delete_a_user_and_remove_it_from_standard_results(): void
    {
        $token = $this->login('admin@assistdoc.local');
        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/users', [
                'full_name' => 'Utente Da Eliminare',
                'email' => 'delete.me@example.test',
                'tenant_id' => (string) $tenant->id,
                'role' => 'viewer',
                'access_methods' => ['password'],
                'status' => 'active',
                'mfa_policy' => 'optional',
                'password' => 'Password12345',
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json('user.id');

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/users/'.$userId)
            ->assertOk()
            ->assertJsonPath('status', 'deleted')
            ->assertJsonPath('removedFromList', true);

        $this->assertNotNull($response->json('deletedAt'));
        $this->assertSame('Admin Demo', $response->json('deletedBy.fullName'));

        $deletedUser = User::withTrashed()->whereKey($userId)->firstOrFail();
        $this->assertNotNull($deletedUser->deleted_at);
        $this->assertSame('1', (string) $deletedUser->deleted_by_user_id);

        $this->withToken($token)
            ->getJson('/api/v1/users?query=delete.me@example.test')
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    #[Test]
    public function deleting_the_same_user_twice_returns_a_conflict(): void
    {
        $token = $this->login('admin@assistdoc.local');
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();

        $this->withToken($token)
            ->deleteJson('/api/v1/users/'.$user->id)
            ->assertOk();

        $this->withToken($token)
            ->deleteJson('/api/v1/users/'.$user->id)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ALREADY_DELETED');
    }

    #[Test]
    public function a_super_admin_cannot_delete_their_own_account(): void
    {
        $token = $this->login('admin@assistdoc.local');
        $user = User::query()->where('email', 'admin@assistdoc.local')->firstOrFail();

        $this->withToken($token)
            ->deleteJson('/api/v1/users/'.$user->id)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'DELETE_NOT_ALLOWED');
    }

    #[Test]
    public function deleting_a_user_outside_the_tenant_scope_returns_not_found(): void
    {
        $token = $this->login('admin@assistdoc.local');
        $foreignUser = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail();

        $this->withToken($token)
            ->deleteJson('/api/v1/users/'.$foreignUser->id)
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
