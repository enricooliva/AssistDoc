<?php

namespace App\Repositories;

use App\Models\LockoutRecord;

class LockoutRecordRepository
{
    public function findActiveForUser(string|int $userId): ?LockoutRecord
    {
        return LockoutRecord::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderByDesc('locked_at')
            ->first();
    }

    public function create(array $attributes): LockoutRecord
    {
        return LockoutRecord::query()->create($attributes);
    }

    public function save(LockoutRecord $record): LockoutRecord
    {
        $record->save();

        return $record->refresh();
    }
}
