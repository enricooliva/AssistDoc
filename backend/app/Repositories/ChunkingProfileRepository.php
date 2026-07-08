<?php

namespace App\Repositories;

use App\Models\ChunkingProfile;

class ChunkingProfileRepository
{
    public function listAll(): array
    {
        return ChunkingProfile::query()->orderByDesc('active')->orderBy('name')->get()->all();
    }

    public function find(string|int $id): ?ChunkingProfile
    {
        return ChunkingProfile::query()->find($id);
    }

    public function firstActive(): ?ChunkingProfile
    {
        return ChunkingProfile::query()->where('active', true)->orderBy('id')->first();
    }

    public function create(array $attributes): ChunkingProfile
    {
        return ChunkingProfile::query()->create($attributes);
    }

    public function save(ChunkingProfile $profile): ChunkingProfile
    {
        $profile->save();

        return $profile->refresh();
    }
}
