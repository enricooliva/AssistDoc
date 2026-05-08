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
            $this->auditService->record('authorization.denied', $user['tenant_id'], $user['id'], [
                'role' => $user['role'],
                'allowedRoles' => $allowedRoles,
            ], 'denied');
        }

        return $allowed;
    }
}

