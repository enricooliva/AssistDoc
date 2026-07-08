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
        private readonly AuthorizationService $authorizationService,
        private readonly AuditService $auditService,
    ) {
    }

    public function listUsers(string $tenantId, int $page = 1, int $perPage = 20, ?string $query = null): array
    {
        return $this->users->paginateForTenant($tenantId, $page, $perPage, $query);
    }

    public function showUser(string $tenantId, string $userId): ?array
    {
        $user = $this->users->findById($userId);

        if (! $user || (string) $user->tenant_id !== $tenantId) {
            return null;
        }

        return ['user' => $this->users->mapEnterpriseUser($user)];
    }

    public function updateUser(array $actor, string $userId, array $attributes): ?array
    {
        $user = $this->users->findById($userId);

        if (! $user) {
            return null;
        }

        if ((string) $actor['role'] === 'tenant-admin' && (string) $user->tenant_id !== (string) $actor['tenant_id']) {
            return $this->authorizationService->denyUserUpdate($actor, 'tenant_scope_mismatch', [
                'target_user_id' => (string) $user->id,
                'target_tenant_id' => (string) $user->tenant_id,
            ]);
        }

        $currentRole = $user->roleAssignment?->role ?? $user->role;

        if ((string) $actor['role'] !== 'super-admin' && $currentRole === 'super-admin') {
            return $this->authorizationService->denyUserUpdate($actor, 'super_admin_edit_not_allowed', [
                'target_user_id' => (string) $user->id,
                'target_role' => $currentRole,
            ]);
        }

        if ((string) $actor['role'] !== 'super-admin' && (string) $attributes['tenant_id'] !== (string) $actor['tenant_id']) {
            return $this->authorizationService->denyUserUpdate($actor, 'tenant_reassignment_not_allowed', [
                'target_user_id' => (string) $user->id,
                'requested_tenant_id' => (string) $attributes['tenant_id'],
            ]);
        }

        if ((string) $actor['role'] !== 'super-admin' && (string) $attributes['role'] === 'super-admin') {
            return $this->authorizationService->denyUserUpdate($actor, 'super_admin_assignment_not_allowed', [
                'target_user_id' => (string) $user->id,
                'requested_role' => $attributes['role'],
            ]);
        }

        $before = [
            'tenant_id' => (string) $user->tenant_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roleAssignment?->role ?? $user->role,
            'status' => $user->status,
            'access_methods' => $user->accessMethods->filter(fn ($method) => $method->enabled)->pluck('method')->values()->all(),
            'mfa_policy' => $user->mfa_policy,
        ];

        $updatedUser = DB::transaction(function () use ($actor, $attributes, $user) {
            $updated = $this->users->updateEnterpriseUser($user, $attributes);

            $this->users->syncAccessMethods($updated, $attributes['access_methods']);
            $this->roleAssignments->syncForUser($updated, $attributes['tenant_id'], $attributes['role'], $actor['id']);

            return $this->users->findById($updated->id);
        });

        if (! $updatedUser) {
            return null;
        }

        $this->auditService->record(
            'user.updated',
            (string) $updatedUser->tenant_id,
            $actor['id'],
            [
                'updated_user_id' => (string) $updatedUser->id,
                'before' => $before,
                'after' => [
                    'tenant_id' => (string) $updatedUser->tenant_id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'role' => $updatedUser->roleAssignment?->role ?? $updatedUser->role,
                    'status' => $updatedUser->status,
                    'access_methods' => $updatedUser->accessMethods->filter(fn ($method) => $method->enabled)->pluck('method')->values()->all(),
                    'mfa_policy' => $updatedUser->mfa_policy,
                ],
            ],
            'success',
            'user',
            $updatedUser->id
        );

        return ['user' => $this->users->mapEnterpriseUser($updatedUser)];
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

    public function deleteUser(array $actor, string $userId): ?array
    {
        $user = $this->users->findByIdIncludingDeleted($userId);

        if (! $user || (string) $user->tenant_id !== (string) $actor['tenant_id']) {
            return null;
        }

        if ((string) $user->id === (string) $actor['id']) {
            return $this->authorizationService->denyUserDelete($actor, 'self_delete_not_allowed', [
                'target_user_id' => (string) $user->id,
            ]);
        }

        if ($user->deleted_at !== null) {
            return [
                'status' => 'already_deleted',
                'userId' => (string) $user->id,
                'deletedAt' => $user->deleted_at?->toIso8601String(),
            ];
        }

        $deletedUser = DB::transaction(function () use ($user, $actor): \App\Models\User {
            $deleted = $this->users->softDelete($user, $actor['id']);

            $this->auditService->record(
                'user.deleted',
                $actor['tenant_id'],
                $actor['id'],
                [
                    'deleted_user_id' => (string) $deleted->id,
                    'previous_status' => $user->status,
                    'deleted_at' => $deleted->deleted_at?->toIso8601String(),
                    'deleted_by_user_id' => $actor['id'],
                ],
                'success',
                'user',
                $deleted->id,
            );

            return $deleted;
        });

        return [
            'status' => 'deleted',
            'userId' => (string) $deletedUser->id,
            'deletedAt' => $deletedUser->deleted_at?->toIso8601String(),
            'deletedBy' => $deletedUser->deleter ? [
                'id' => (string) $deletedUser->deleter->id,
                'fullName' => $deletedUser->deleter->name,
            ] : [
                'id' => (string) $actor['id'],
                'fullName' => 'Utente non disponibile',
            ],
            'removedFromList' => true,
        ];
    }
}
