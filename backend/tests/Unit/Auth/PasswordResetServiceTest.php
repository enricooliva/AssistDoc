<?php

namespace Tests\Unit\Auth;

use App\Services\Auth\PasswordResetService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_reset_identifier_for_managed_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $service = app(PasswordResetService::class);
        $result = $service->request('viewer@assistdoc.local', '127.0.0.1');

        $this->assertSame('accepted', $result['status']);
        $this->assertNotEmpty($result['reset_id']);
    }
}
