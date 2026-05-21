<?php

namespace App\Repositories;

use App\Models\RoleAssignment;

class RoleAssignmentRepository
{
    public function assign(string|int $tenantId, string|int $userId, string $role, ?string $assignedByUserId = null): RoleAssignment
    {
        return RoleAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId],
            ['role' => $role, 'assigned_by_user_id' => $assignedByUserId]
        );
    }
}
