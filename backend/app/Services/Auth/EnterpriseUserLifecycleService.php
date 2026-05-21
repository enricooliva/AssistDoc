<?php

namespace App\Services\Auth;

use App\Repositories\RoleAssignmentRepository;
use App\Repositories\UserRepository;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnterpriseUserLifecycleService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleAssignmentRepository $roleAssignments,
        private readonly MfaLockoutService $mfaLockoutService,
        private readonly AuditService $auditService,
    ) {
    }

    public function listUsers(string $tenantId, int $page = 1, int $perPage = 20): array
    {
        return $this->users->paginateForTenant($tenantId, $page, $perPage);
    }

    public function showUser(string $tenantId, string $userId): ?array
    {
        $user = $this->users->findById($userId);

        if (! $user || (string) $user->tenant_id !== $tenantId) {
            return null;
        }

        return ['user' => $this->users->mapEnterpriseUser($user)];
    }

    public function createUser(string $actorTenantId, string $actorUserId, array $attributes): array
    {
        $user = DB::transaction(function () use ($actorTenantId, $actorUserId, $attributes) {
            $user = $this->users->create([
                'tenant_id' => $attributes['tenant_id'],
                'name' => $attributes['full_name'],
                'email' => mb_strtolower($attributes['email']),
                'password' => Hash::make($attributes['password'] ?? 'password12345'),
                'role' => $attributes['role'],
                'auth_provider' => in_array('password', $attributes['access_methods'], true) ? 'local' : 'company_account',
                'status' => $attributes['status'],
                'mfa_policy' => $attributes['mfa_policy'] ?? 'optional',
                'password_reset_required' => in_array('password', $attributes['access_methods'], true) && empty($attributes['password']),
            ]);

            $this->users->syncAccessMethods($user, $attributes['access_methods']);
            $this->roleAssignments->assign($attributes['tenant_id'], $user->id, $attributes['role'], $actorUserId);

            return $this->users->findById($user->id);
        });

        $this->auditService->record(
            'user.provisioned',
            $actorTenantId,
            $actorUserId,
            ['provisioned_user_id' => (string) $user->id, 'status' => $user->status],
            'success',
            'user',
            $user->id
        );

        return ['user' => $this->users->mapEnterpriseUser($user)];
    }

    public function updateStatus(string $actorTenantId, string $actorUserId, string $userId, string $status, ?string $reason = null): ?array
    {
        $user = $this->users->findById($userId);
        if (! $user || (string) $user->tenant_id !== $actorTenantId) {
            return null;
        }

        $allowed = [
            'active' => ['suspended', 'locked', 'provisioned', 'reset_pending'],
            'suspended' => ['active'],
            'locked' => ['active'],
            'deactivated' => ['active', 'suspended', 'locked'],
        ];

        if (! in_array($user->status, $allowed[$status] ?? [], true) && $user->status !== $status) {
            return ['status' => 'invalid_transition'];
        }

        $user->status = $status;
        if ($status !== 'locked') {
            $user->locked_at = null;
            $user->locked_until = null;
            $user->lockout_reason = null;
        }

        $user = $this->users->save($user);

        $this->auditService->record(
            'user.status_changed',
            $actorTenantId,
            $actorUserId,
            ['affected_user_id' => (string) $user->id, 'status' => $status, 'reason' => $reason],
            'success',
            'user',
            $user->id
        );

        return ['user' => $this->users->mapEnterpriseUser($user)];
    }

    public function unlockUser(string $actorTenantId, string $actorUserId, string $userId): ?array
    {
        $user = $this->users->findById($userId);
        if (! $user || (string) $user->tenant_id !== $actorTenantId) {
            return null;
        }

        $user = $this->mfaLockoutService->release($user, $actorUserId);

        return ['user' => $this->users->mapEnterpriseUser($user)];
    }
}
