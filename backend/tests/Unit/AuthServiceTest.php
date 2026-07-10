<?php

namespace Tests\Unit;

use App\Repositories\UserRepository;
use App\Services\Audit\AuditService;
use App\Services\Auth\AuthService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    #[Test]
    public function inspect_token_marks_missing_token_as_invalid(): void
    {
        $service = new AuthService(
            Mockery::mock(UserRepository::class),
            Mockery::mock(AuditService::class),
        );

        $result = $service->inspectToken(null);

        $this->assertFalse($result['valid']);
        $this->assertSame('missing_token', $result['reason']);
    }

    #[Test]
    public function inspect_token_rejects_malformed_token(): void
    {
        $service = new AuthService(
            Mockery::mock(UserRepository::class),
            Mockery::mock(AuditService::class),
        );

        $result = $service->inspectToken('not-a-jwt');

        $this->assertFalse($result['valid']);
        $this->assertSame('malformed_token', $result['reason']);
    }
}
