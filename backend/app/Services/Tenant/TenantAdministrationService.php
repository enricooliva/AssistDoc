<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\RoleAssignmentRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantAdministrationService
{
    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly UserRepository $users,
        private readonly RoleAssignmentRepository $roleAssignments,
        private readonly AuditService $auditService,
    ) {
    }

    public function listTenants(int $page = 1, int $perPage = 20): array
    {
        return $this->tenants->paginateForReview($page, $perPage);
    }

    public function showTenant(string $tenantId): ?array
    {
        $tenant = $this->tenants->findWithMembers($tenantId);

        if (! $tenant) {
            return null;
        }

        return $this->tenants->mapTenantDetail($tenant, $this->users);
    }

    public function updateTenant(array $actor, string $tenantId, array $attributes): ?array
    {
        $tenant = $this->tenants->findSummaryById($tenantId);

        if (! $tenant) {
            return null;
        }

        $before = [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status,
        ];

        $tenant->fill([
            'name' => $attributes['tenant_name'],
            'slug' => Str::slug($attributes['tenant_slug']),
            'status' => $attributes['status'],
        ]);

        $tenant = $this->tenants->save($tenant);
        $summary = $this->tenants->findSummaryById((string) $tenant->id) ?? $tenant;

        $this->auditService->record(
            'tenant.updated',
            (string) $actor['tenant_id'],
            (string) $actor['id'],
            [
                'tenant_id' => (string) $tenant->id,
                'before' => $before,
                'after' => [
                    'name' => $summary->name,
                    'slug' => $summary->slug,
                    'status' => $summary->status,
                ],
            ],
            'success',
            'tenant',
            $summary->id
        );

        return [
            'tenant' => $this->tenants->mapTenantSummary($summary),
        ];
    }

    public function createTenant(array $actor, array $attributes): array
    {
        $result = DB::transaction(function () use ($actor, $attributes): array {
            $tenant = $this->tenants->create([
                'name' => $attributes['tenant_name'],
                'slug' => Str::slug($attributes['tenant_slug']),
                'status' => 'active',
            ]);

            $passwordEnabled = in_array('password', $attributes['admin_access_methods'], true);
            $role = $attributes['admin_role'] ?? 'tenant-admin';

            $user = $this->users->create([
                'tenant_id' => $tenant->id,
                'name' => $attributes['admin_full_name'],
                'email' => mb_strtolower($attributes['admin_email']),
                'password' => Hash::make($attributes['admin_password'] ?? 'password12345'),
                'role' => $role,
                'auth_provider' => $passwordEnabled ? 'local' : 'company_account',
                'status' => $attributes['admin_status'],
                'mfa_policy' => $attributes['admin_mfa_policy'] ?? 'optional',
                'password_reset_required' => $passwordEnabled && empty($attributes['admin_password']),
            ]);

            $this->users->syncAccessMethods($user, $attributes['admin_access_methods']);
            $this->roleAssignments->assign($tenant->id, $user->id, $role, $actor['id']);

            $tenant = $this->tenants->findWithMembers($tenant->id) ?? $tenant->loadCount(['users', 'roleAssignments']);
            $initialAdmin = $this->users->findById((string) $user->id) ?? $user;

            $this->auditService->record(
                'tenant.created',
                (string) $actor['tenant_id'],
                (string) $actor['id'],
                [
                    'tenant_id' => (string) $tenant->id,
                    'tenant_name' => $tenant->name,
                    'tenant_slug' => $tenant->slug,
                    'initial_admin_id' => (string) $initialAdmin->id,
                    'initial_admin_role' => $role,
                ],
                'success',
                'tenant',
                $tenant->id
            );

            $this->auditService->record(
                'user.provisioned',
                (string) $tenant->id,
                (string) $actor['id'],
                [
                    'provisioned_user_id' => (string) $initialAdmin->id,
                    'status' => $initialAdmin->status,
                    'tenant_id' => (string) $tenant->id,
                    'tenant_name' => $tenant->name,
                    'provisioning_kind' => 'initial_admin',
                ],
                'success',
                'user',
                $initialAdmin->id
            );

            return [
                'tenant' => $this->tenants->mapTenantSummary($tenant),
                'initial_admin' => $this->users->mapEnterpriseUser($initialAdmin),
            ];
        });

        return $result;
    }

    public function addUser(array $actor, string $tenantId, array $attributes): ?array
    {
        $tenant = $this->tenants->findSummaryById($tenantId);

        if (! $tenant) {
            return null;
        }

        if ($tenant->status !== 'active') {
            return [
                'status' => 'tenant_inactive',
                'tenantId' => (string) $tenant->id,
            ];
        }

        if ($this->users->findByEmail($attributes['email'])) {
            return [
                'status' => 'duplicate_user',
                'field' => 'email',
            ];
        }

        $user = DB::transaction(function () use ($actor, $tenant, $attributes): User {
            $passwordEnabled = in_array('password', $attributes['access_methods'], true);
            $user = $this->users->create([
                'tenant_id' => $tenant->id,
                'name' => $attributes['full_name'],
                'email' => mb_strtolower($attributes['email']),
                'password' => Hash::make($attributes['password'] ?? 'password12345'),
                'role' => $attributes['role'],
                'auth_provider' => $passwordEnabled ? 'local' : 'company_account',
                'status' => $attributes['status'],
                'mfa_policy' => $attributes['mfa_policy'] ?? 'optional',
                'password_reset_required' => $passwordEnabled && empty($attributes['password']),
            ]);

            $this->users->syncAccessMethods($user, $attributes['access_methods']);
            $this->roleAssignments->assign($tenant->id, $user->id, $attributes['role'], $actor['id']);
            $user = $this->users->findById((string) $user->id) ?? $user;

            $this->auditService->record(
                'user.provisioned',
                (string) $tenant->id,
                (string) $actor['id'],
                [
                    'provisioned_user_id' => (string) $user->id,
                    'status' => $user->status,
                    'tenant_id' => (string) $tenant->id,
                    'tenant_name' => $tenant->name,
                    'provisioning_kind' => 'additional_user',
                ],
                'success',
                'user',
                $user->id
            );

            return $user;
        });

        $tenantDetail = $this->tenants->findWithMembers($tenant->id) ?? $tenant;

        return [
            'tenant' => $this->tenants->mapTenantSummary($tenantDetail),
            'user' => $this->users->mapEnterpriseUser($user),
        ];
    }
}
