<?php

namespace App\Repositories;

use App\Models\PasswordResetJourney;

class PasswordResetJourneyRepository
{
    public function invalidateActiveForUser(string|int $userId): void
    {
        PasswordResetJourney::query()
            ->where('user_id', $userId)
            ->where('status', 'initiated')
            ->update(['status' => 'cancelled']);
    }

    public function create(array $attributes): PasswordResetJourney
    {
        return PasswordResetJourney::query()->create($attributes);
    }

    public function findValidByToken(string $token): ?PasswordResetJourney
    {
        return PasswordResetJourney::query()
            ->with(['user.tenant', 'user.roleAssignment', 'user.accessMethods'])
            ->where('reset_token', $token)
            ->where('status', 'initiated')
            ->first();
    }

    public function save(PasswordResetJourney $journey): PasswordResetJourney
    {
        $journey->save();

        return $journey->refresh();
    }
}
