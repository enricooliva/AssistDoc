<?php

namespace Tests\Feature\Contract;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\EnterpriseUserAccessFixtures;
use Tests\TestCase;

class EnterpriseUserEditContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function patching_a_user_returns_the_updated_summary_envelope(): void
    {
        $token = EnterpriseUserAccessFixtures::login($this, 'admin@assistdoc.local');
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();

        $response = $this->withToken($token)
            ->patchJson('/api/v1/users/'.$user->id, [
                'full_name' => 'Viewer Contratto',
                'email' => 'viewer.contract@example.test',
                'tenant_id' => (string) $user->tenant_id,
                'role' => 'viewer',
                'status' => 'active',
                'access_methods' => ['password'],
                'mfa_policy' => 'optional',
                'password' => 'Password12345',
            ])
            ->assertOk();

        $response->assertJsonPath('user.full_name', 'Viewer Contratto');
        $response->assertJsonPath('user.email', 'viewer.contract@example.test');
        $response->assertJsonPath('user.role', 'viewer');
        $response->assertJsonPath('user.status', 'active');
    }

}
