<?php

namespace App\DataTransferObjects\Rag;

use App\Models\RetrievalModelProfile;

class RetrievalModelProfileData
{
    public static function fromModel(RetrievalModelProfile $profile): array
    {
        return [
            'id' => (string) $profile->id,
            'name' => $profile->name,
            'slug' => $profile->slug,
            'generationModel' => $profile->generation_model,
            'embeddingModel' => $profile->embedding_model,
            'tokenizerKey' => $profile->tokenizer_key,
            'tokenWindow' => $profile->token_window,
            'embeddingDimensions' => $profile->embedding_dimensions,
            'availableForNewRuns' => $profile->available_for_new_runs,
        ];
    }
}
