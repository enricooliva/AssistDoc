<?php

namespace App\Repositories;

use App\Models\RoleAssignment;
use App\Models\User;

class RoleAssignmentRepository
{
    public function assign(string|int $tenantId, string|int $userId, string $role, ?string $assignedByUserId = null): RoleAssignment
    {
        return RoleAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId],
            ['role' => $role, 'assigned_by_user_id' => $assignedByUserId]
        );
    }

    public function syncForUser(User $user, string|int $tenantId, string $role, ?string $assignedByUserId = null): RoleAssignment
    {
        $assignment = RoleAssignment::query()->where('user_id', $user->id)->first();

        if ($assignment) {
            $assignment->forceFill([
                'tenant_id' => $tenantId,
                'role' => $role,
                'assigned_by_user_id' => $assignedByUserId,
            ])->save();

            return $assignment->refresh();
        }

        return $this->assign($tenantId, $user->id, $role, $assignedByUserId);
    }
}
