<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserAccessMethod;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    private function baseQuery()
    {
        return User::query()->with(['tenant', 'roleAssignment', 'accessMethods', 'deleter']);
    }

    private function trashedBaseQuery()
    {
        return User::query()->withTrashed()->with(['tenant', 'roleAssignment', 'accessMethods', 'deleter']);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->baseQuery()
            ->where('email', mb_strtolower($email))
            ->first();
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->baseQuery()
            ->where('email', mb_strtolower($email))
            ->where('status', 'active')
            ->first();
    }

    public function findActiveById(int|string $id): ?User
    {
        return $this->baseQuery()
            ->whereKey($id)
            ->where('status', 'active')
            ->first();
    }

    public function findById(int|string $id): ?User
    {
        return $this->baseQuery()->whereKey($id)->first();
    }

    public function findByIdIncludingDeleted(int|string $id): ?User
    {
        return $this->trashedBaseQuery()->whereKey($id)->first();
    }

    public function paginateForTenant(string $tenantId, int $page = 1, int $perPage = 20, ?string $query = null): array
    {
        $normalizedQuery = trim((string) $query);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->baseQuery()
            ->where('tenant_id', $tenantId)
            ->when($normalizedQuery !== '', function ($builder) use ($normalizedQuery) {
                $builder->where(function ($queryBuilder) use ($normalizedQuery): void {
                    $queryBuilder
                        ->where('name', 'like', '%'.$normalizedQuery.'%')
                        ->orWhere('email', 'like', '%'.$normalizedQuery.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => collect($paginator->items())
                ->map(fn (User $user): array => $this->mapEnterpriseUser($user))
                ->all(),
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function save(User $user): User
    {
        $user->save();

        return $user->refresh()->load(['tenant', 'roleAssignment', 'accessMethods', 'deleter']);
    }

    public function softDelete(User $user, string|int $deletedByUserId): User
    {
        $user->deleted_by_user_id = $deletedByUserId;
        $user->save();
        $user->delete();

        return $this->findByIdIncludingDeleted($user->id) ?? $user;
    }

    public function syncAccessMethods(User $user, array $methods, string $managedBy = 'platform'): void
    {
        $current = $user->accessMethods()->get()->keyBy('method');

        foreach (['company_account', 'password'] as $method) {
            $enabled = in_array($method, $methods, true);
            $record = $current->get($method);

            if ($record) {
                $record->forceFill([
                    'enabled' => $enabled,
                    'managed_by' => $managedBy,
                ])->save();
                continue;
            }

            UserAccessMethod::query()->create([
                'user_id' => $user->id,
                'method' => $method,
                'enabled' => $enabled,
                'managed_by' => $managedBy,
            ]);
        }
    }

    public function hasAccessMethod(User $user, string $method): bool
    {
        return $user->accessMethods->contains(
            fn (UserAccessMethod $accessMethod): bool => $accessMethod->method === $method && $accessMethod->enabled
        );
    }

    public function mapEnterpriseUser(User $user): array
    {
        $lockout = $user->lockoutRecords()->where('status', 'active')->latest('locked_at')->first();

        return [
            'id' => (string) $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'tenant' => [
                'id' => (string) $user->tenant_id,
                'name' => $user->tenant?->name,
                'slug' => $user->tenant?->slug,
            ],
            'role' => $user->roleAssignment?->role ?? $user->role,
            'status' => $user->status,
            'access_methods' => $user->accessMethods
                ->filter(fn (UserAccessMethod $method): bool => $method->enabled)
                ->pluck('method')
                ->values()
                ->all(),
            'mfa_policy' => $user->mfa_policy,
            'deleted_at' => $user->deleted_at?->toIso8601String(),
            'deleted_by' => $user->deleter ? [
                'id' => (string) $user->deleter->id,
                'full_name' => $user->deleter->name,
            ] : null,
            'lockout' => $lockout ? [
                'status' => $lockout->status,
                'reason' => $user->lockout_reason,
                'locked_until' => $user->locked_until?->toIso8601String(),
            ] : null,
        ];
    }
}
