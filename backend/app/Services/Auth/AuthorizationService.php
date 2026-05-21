<?php

namespace App\Services\Auth;

use App\Services\Audit\AuditService;

class AuthorizationService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function canAccess(array $user, array $allowedRoles): bool
    {
        $allowed = in_array($user['role'], $allowedRoles, true);

        if (! $allowed) {
            $this->auditService->record('auth.role_denied', $user['tenant_id'], $user['id'], [
                'role' => $user['role'],
                'allowedRoles' => $allowedRoles,
            ], 'denied');
        }

        return $allowed;
    }

    public function denyLifecycleState(array $user, string $reason, array $context = []): array
    {
        $this->auditService->record('auth.lifecycle_denied', $user['tenant_id'], $user['id'], array_merge([
            'reason' => $reason,
        ], $context), 'denied');

        return [
            'status' => 'denied',
            'error' => [
                'code' => 'ACCESS_DENIED',
                'message' => 'L\'account non è autorizzato a completare questa operazione.',
            ],
        ];
    }
}
