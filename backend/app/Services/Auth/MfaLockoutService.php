<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\LockoutRecordRepository;
use App\Repositories\UserRepository;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Cache;

class MfaLockoutService
{
    private const PASSWORD_FAILURE_LIMIT = 5;
    private const MFA_FAILURE_LIMIT = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct(
        private readonly LockoutRecordRepository $lockoutRecords,
        private readonly UserRepository $users,
        private readonly AuditService $auditService,
    ) {
    }

    public function recordPasswordFailure(User $user): void
    {
        $count = Cache::increment($this->passwordKey($user), 1);
        Cache::put($this->passwordKey($user), $count, now()->addMinutes(self::LOCKOUT_MINUTES));

        if ($count >= self::PASSWORD_FAILURE_LIMIT) {
            $this->lockUser($user, 'password_failures', self::PASSWORD_FAILURE_LIMIT, $count);
        }
    }

    public function clearPasswordFailures(User $user): void
    {
        Cache::forget($this->passwordKey($user));
    }

    public function recordMfaFailure(User $user): void
    {
        $count = Cache::increment($this->mfaKey($user), 1);
        Cache::put($this->mfaKey($user), $count, now()->addMinutes(self::LOCKOUT_MINUTES));

        if ($count >= self::MFA_FAILURE_LIMIT) {
            $this->lockUser($user, 'mfa_failures', self::MFA_FAILURE_LIMIT, $count);
        }
    }

    public function clearMfaFailures(User $user): void
    {
        Cache::forget($this->mfaKey($user));
    }

    public function activeLockoutFor(User $user): ?array
    {
        $record = $this->lockoutRecords->findActiveForUser($user->id);

        if (! $record) {
            return null;
        }

        if ($record->locked_until !== null && $record->locked_until->isPast()) {
            $record->status = 'expired';
            $record->released_at = now();
            $this->lockoutRecords->save($record);

            $user->status = 'active';
            $user->locked_at = null;
            $user->locked_until = null;
            $user->lockout_reason = null;
            $this->users->save($user);

            return null;
        }

        return [
            'reason' => $user->lockout_reason,
            'lockedUntil' => $user->locked_until?->toIso8601String(),
        ];
    }

    public function release(User $user, string|int $releasedByUserId): User
    {
        $record = $this->lockoutRecords->findActiveForUser($user->id);
        if ($record) {
            $record->status = 'released';
            $record->released_at = now();
            $record->released_by_user_id = $releasedByUserId;
            $this->lockoutRecords->save($record);
        }

        $user->status = 'active';
        $user->locked_at = null;
        $user->locked_until = null;
        $user->lockout_reason = null;
        $user = $this->users->save($user);

        $this->clearPasswordFailures($user);
        $this->clearMfaFailures($user);

        $this->auditService->record(
            'auth.lockout_released',
            (string) $user->tenant_id,
            (string) $releasedByUserId,
            ['released_user_id' => (string) $user->id],
            'success',
            'user',
            $user->id
        );

        return $user;
    }

    private function lockUser(User $user, string $triggerType, int $threshold, int $count): void
    {
        $lockedUntil = now()->addMinutes(self::LOCKOUT_MINUTES);
        $this->lockoutRecords->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'trigger_type' => $triggerType,
            'status' => 'active',
            'failure_threshold' => $threshold,
            'failure_count' => $count,
            'locked_at' => now(),
            'locked_until' => $lockedUntil,
        ]);

        $user->status = 'locked';
        $user->locked_at = now();
        $user->locked_until = $lockedUntil;
        $user->lockout_reason = $triggerType;
        $this->users->save($user);

        $this->auditService->record(
            'auth.account_locked',
            (string) $user->tenant_id,
            (string) $user->id,
            ['trigger_type' => $triggerType, 'failure_count' => $count],
            'denied',
            'user',
            $user->id
        );
    }

    private function passwordKey(User $user): string
    {
        return sprintf('auth:password-failures:%s', $user->id);
    }

    private function mfaKey(User $user): string
    {
        return sprintf('auth:mfa-failures:%s', $user->id);
    }
}
