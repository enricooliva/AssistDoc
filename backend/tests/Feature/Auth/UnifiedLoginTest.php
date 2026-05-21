<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_returns_the_unified_login_options(): void
    {
        $this->getJson('/api/v1/auth/options')
            ->assertOk()
            ->assertJsonCount(2, 'options')
            ->assertJsonPath('options.0.id', 'company_account')
            ->assertJsonPath('options.1.id', 'password');
    }

    #[Test]
    public function it_authenticates_a_password_user_through_the_unified_endpoint(): void
    {
        $this->postJson('/api/v1/auth/password/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('status', 'authenticated')
            ->assertJsonPath('user.email', 'viewer@assistdoc.local');
    }

    #[Test]
    public function it_requires_password_reset_when_the_flag_is_active(): void
    {
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $user->forceFill(['password_reset_required' => true])->save();

        $this->postJson('/api/v1/auth/password/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->assertStatus(409)
            ->assertJsonPath('status', 'action_required')
            ->assertJsonPath('action', 'password_reset_required');
    }
}
