<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_starts_and_completes_a_password_reset_journey(): void
    {
        $startResponse = $this->postJson('/api/v1/auth/password-reset', [
            'email' => 'viewer@assistdoc.local',
        ])->assertAccepted()
            ->assertJsonPath('status', 'accepted');

        $this->postJson('/api/v1/auth/password-reset/complete', [
            'reset_id' => (string) $startResponse->json('reset_id'),
            'new_password' => 'NuovaPassword123',
        ])->assertOk()
            ->assertJsonPath('status', 'completed');
    }

    #[Test]
    public function it_rejects_password_reset_for_a_company_account_only_user(): void
    {
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $user->accessMethods()->delete();
        $user->accessMethods()->create([
            'method' => 'company_account',
            'enabled' => true,
            'managed_by' => 'platform',
        ]);

        $this->postJson('/api/v1/auth/password-reset', [
            'email' => 'viewer@assistdoc.local',
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'ACCESS_DENIED');
    }
}
