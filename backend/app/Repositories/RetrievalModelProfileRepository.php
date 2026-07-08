<?php

namespace App\Repositories;

use App\Models\RetrievalModelProfile;

class RetrievalModelProfileRepository
{
    public function listAll(): array
    {
        return RetrievalModelProfile::query()
            ->orderByDesc('available_for_new_runs')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function find(string|int $id): ?RetrievalModelProfile
    {
        return RetrievalModelProfile::query()->find($id);
    }

    public function findBySlug(string $slug): ?RetrievalModelProfile
    {
        return RetrievalModelProfile::query()->where('slug', $slug)->first();
    }

    public function firstAvailable(): ?RetrievalModelProfile
    {
        return RetrievalModelProfile::query()
            ->where('available_for_new_runs', true)
            ->orderBy('id')
            ->first();
    }
}
