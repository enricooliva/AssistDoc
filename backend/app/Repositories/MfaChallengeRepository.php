<?php

namespace App\Repositories;

use App\Models\MfaChallenge;

class MfaChallengeRepository
{
    public function create(array $attributes): MfaChallenge
    {
        return MfaChallenge::query()->create($attributes);
    }

    public function findPending(string|int $id): ?MfaChallenge
    {
        return MfaChallenge::query()
            ->with(['user.tenant', 'user.roleAssignment', 'user.accessMethods'])
            ->whereKey($id)
            ->where('status', 'pending')
            ->first();
    }

    public function save(MfaChallenge $challenge): MfaChallenge
    {
        $challenge->save();

        return $challenge->refresh();
    }
}
