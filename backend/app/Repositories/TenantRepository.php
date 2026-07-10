<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TenantRepository
{
    private function summaryQuery()
    {
        return Tenant::query()
            ->withCount([
                'users',
                'roleAssignments as admin_count' => function ($query): void {
                    $query->whereIn('role', ['super-admin', 'tenant-admin', 'operator']);
                },
            ])
            ->withMax('users', 'created_at');
    }

    public function paginateForReview(int $page = 1, int $perPage = 20): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->summaryQuery()
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => collect($paginator->items())
                ->map(fn (Tenant $tenant): array => $this->mapTenantSummary($tenant))
                ->all(),
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function findSummaryById(int|string $tenantId): ?Tenant
    {
        return $this->summaryQuery()->whereKey($tenantId)->first();
    }

    public function findWithMembers(int|string $tenantId): ?Tenant
    {
        return Tenant::query()
            ->withCount([
                'users',
                'roleAssignments as admin_count' => function ($query): void {
                    $query->whereIn('role', ['super-admin', 'tenant-admin', 'operator']);
                },
            ])
            ->withMax('users', 'created_at')
            ->with([
                'users' => function ($query): void {
                    $query->with(['tenant', 'roleAssignment', 'accessMethods', 'deleter', 'lockoutRecords'])
                        ->orderBy('name');
                },
            ])
            ->whereKey($tenantId)
            ->first();
    }

    public function create(array $attributes): Tenant
    {
        return Tenant::query()->create($attributes);
    }

    public function save(Tenant $tenant): Tenant
    {
        $tenant->save();

        return $tenant->refresh();
    }

    public function mapTenantSummary(Tenant $tenant): array
    {
        $lastProvisionedAt = $tenant->getAttribute('users_max_created_at');

        if ($lastProvisionedAt instanceof CarbonInterface) {
            $lastProvisionedAt = $lastProvisionedAt->toIso8601String();
        } elseif (is_string($lastProvisionedAt) && $lastProvisionedAt !== '') {
            $lastProvisionedAt = \Carbon\CarbonImmutable::parse($lastProvisionedAt)->toIso8601String();
        } else {
            $lastProvisionedAt = null;
        }

        return [
            'id' => (string) $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status,
            'member_count' => (int) ($tenant->users_count ?? 0),
            'admin_count' => (int) ($tenant->admin_count ?? 0),
            'last_provisioned_at' => $lastProvisionedAt,
        ];
    }

    public function mapTenantDetail(Tenant $tenant, UserRepository $users): array
    {
        return [
            'tenant' => $this->mapTenantSummary($tenant),
            'members' => collect($tenant->users ?? [])
                ->map(fn (User $user): array => $users->mapEnterpriseUser($user))
                ->all(),
        ];
    }
}
