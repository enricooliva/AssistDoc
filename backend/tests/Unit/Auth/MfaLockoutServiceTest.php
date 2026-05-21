<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Services\Auth\MfaLockoutService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MfaLockoutServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_locks_and_releases_a_user_after_repeated_password_failures(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $service = app(MfaLockoutService::class);

        for ($i = 0; $i < 5; $i++) {
            $service->recordPasswordFailure($user);
            $user = $user->fresh();
        }

        $this->assertSame('locked', $user->status);

        $service->release($user, (string) $user->id);

        $this->assertSame('active', $user->fresh()->status);
    }
}
