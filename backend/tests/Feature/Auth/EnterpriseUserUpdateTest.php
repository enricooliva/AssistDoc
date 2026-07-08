<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\EnterpriseUserAccessFixtures;
use Tests\TestCase;

class EnterpriseUserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_tenant_admin_can_update_users_within_its_tenant_scope(): void
    {
        $token = EnterpriseUserAccessFixtures::login($this, 'tenant-admin@assistdoc.local');
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();

        $response = $this->withToken($token)
            ->patchJson('/api/v1/users/'.$user->id, [
                'full_name' => 'Viewer Aggiornato',
                'email' => 'viewer.updated@example.test',
                'tenant_id' => (string) $user->tenant_id,
                'role' => 'operator',
                'status' => 'suspended',
                'access_methods' => ['company_account'],
                'mfa_policy' => 'required',
                'password' => 'Password12345',
            ])
            ->assertOk()
            ->assertJsonPath('user.full_name', 'Viewer Aggiornato')
            ->assertJsonPath('user.email', 'viewer.updated@example.test')
            ->assertJsonPath('user.role', 'operator')
            ->assertJsonPath('user.status', 'suspended')
            ->assertJsonPath('user.access_methods.0', 'company_account');

        $updatedUser = User::query()->with(['roleAssignment', 'accessMethods'])->findOrFail($user->id);

        $this->assertSame('Viewer Aggiornato', $updatedUser->name);
        $this->assertSame('viewer.updated@example.test', $updatedUser->email);
        $this->assertSame('operator', $updatedUser->roleAssignment?->role);
        $this->assertSame(['company_account'], $updatedUser->accessMethods->where('enabled', true)->pluck('method')->values()->all());
        $this->assertNotNull($response->json('user'));
    }

    #[Test]
    public function a_super_admin_can_move_a_user_to_another_tenant(): void
    {
        $token = EnterpriseUserAccessFixtures::login($this, 'admin@assistdoc.local');
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->firstOrFail();
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();

        $this->withToken($token)
            ->patchJson('/api/v1/users/'.$user->id, [
                'full_name' => 'Viewer Spostato',
                'email' => 'viewer.moved@example.test',
                'tenant_id' => (string) $tenantB->id,
                'role' => 'viewer',
                'status' => 'active',
                'access_methods' => ['password'],
                'mfa_policy' => 'optional',
                'password' => 'Password12345',
            ])
            ->assertOk()
            ->assertJsonPath('user.tenant.id', (string) $tenantB->id)
            ->assertJsonPath('user.role', 'viewer');

        $movedUser = User::query()->with(['roleAssignment'])->findOrFail($user->id);

        $this->assertSame((string) $tenantB->id, (string) $movedUser->tenant_id);
        $this->assertSame((string) $tenantB->id, (string) $movedUser->roleAssignment?->tenant_id);
        $this->assertSame('viewer', $movedUser->roleAssignment?->role);
    }

    #[Test]
    public function a_tenant_admin_cannot_edit_a_super_admin_record(): void
    {
        $token = EnterpriseUserAccessFixtures::login($this, 'tenant-admin@assistdoc.local');
        $user = User::query()->where('email', 'admin@assistdoc.local')->firstOrFail();

        $this->withToken($token)
            ->patchJson('/api/v1/users/'.$user->id, [
                'full_name' => 'Admin Modificato',
                'email' => 'admin.updated@example.test',
                'tenant_id' => (string) $user->tenant_id,
                'role' => 'super-admin',
                'status' => 'active',
                'access_methods' => ['company_account'],
                'mfa_policy' => 'optional',
                'password' => 'Password12345',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }

}
