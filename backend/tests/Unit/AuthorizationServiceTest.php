<?php

namespace Tests\Unit;

use App\Services\Audit\AuditService;
use App\Services\Auth\AuthorizationService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    #[Test]
    public function it_allows_access_when_role_is_permitted(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldNotReceive('record');

        $service = new AuthorizationService($audit);

        $allowed = $service->canAccess([
            'id' => '1',
            'tenant_id' => '1',
            'role' => 'viewer',
        ], ['viewer', 'operator']);

        $this->assertTrue($allowed);
    }

    #[Test]
    public function it_records_audit_when_role_is_denied(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')
            ->once()
            ->with('auth.role_denied', '1', '1', Mockery::type('array'), 'denied');

        $service = new AuthorizationService($audit);

        $allowed = $service->canAccess([
            'id' => '1',
            'tenant_id' => '1',
            'role' => 'viewer',
        ], ['super-admin']);

        $this->assertFalse($allowed);
    }
}
