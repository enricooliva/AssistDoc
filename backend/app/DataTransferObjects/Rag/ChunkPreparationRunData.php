<?php

namespace App\DataTransferObjects\Rag;

use App\Models\ChunkPreparationRun;

class ChunkPreparationRunData
{
    public static function fromModel(ChunkPreparationRun $run): array
    {
        return [
            'id' => (string) $run->id,
            'documentId' => (string) $run->document_id,
            'retrievalModelProfileId' => (string) $run->retrieval_model_profile_id,
            'chunkingProfileId' => (string) $run->chunking_profile_id,
            'status' => $run->status,
            'createdAt' => $run->created_at?->toIso8601String(),
            'failure' => $run->failure_message ? [
                'code' => $run->failure_code,
                'message' => $run->failure_message,
                'measuredTokenCount' => $run->validationFailures->first()?->measured_token_count,
                'allowedTokenCount' => $run->validationFailures->first()?->allowed_token_count,
            ] : null,
            'activatedProfileCompatibility' => [
                'retrievalModelProfileId' => (string) $run->retrieval_model_profile_id,
                'chunkingProfileId' => (string) $run->chunking_profile_id,
            ],
        ];
    }
}
