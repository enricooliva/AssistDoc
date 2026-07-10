<?php

namespace App\DataTransferObjects\Rag;

use App\Models\ChunkingProfile;

class ChunkingProfileData
{
    public static function fromModel(ChunkingProfile $profile): array
    {
        return [
            'id' => (string) $profile->id,
            'name' => $profile->name,
            'slug' => $profile->slug,
            'chunkSizeTokens' => $profile->chunk_size_tokens,
            'overlapTokens' => $profile->overlap_tokens,
            'active' => $profile->active,
            'notes' => $profile->notes,
        ];
    }
}
