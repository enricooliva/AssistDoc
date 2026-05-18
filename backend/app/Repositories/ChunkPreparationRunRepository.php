<?php

namespace App\Repositories;

use App\Models\ChunkPreparationRun;
use App\Models\Document;
use App\Models\PreparationValidationFailure;

class ChunkPreparationRunRepository
{
    public function create(array $attributes): ChunkPreparationRun
    {
        return ChunkPreparationRun::query()->create($attributes);
    }

    public function save(ChunkPreparationRun $run): ChunkPreparationRun
    {
        $run->save();

        return $run->refresh(['retrievalModelProfile', 'chunkingProfile', 'validationFailures']);
    }

    public function findForDocument(string $tenantId, string $documentId, string $runId): ?ChunkPreparationRun
    {
        return ChunkPreparationRun::query()
            ->with(['retrievalModelProfile', 'chunkingProfile', 'validationFailures'])
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->whereKey($runId)
            ->first();
    }

    public function latestForDocument(Document $document): ?ChunkPreparationRun
    {
        return ChunkPreparationRun::query()
            ->where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->latest('id')
            ->first();
    }

    public function createValidationFailure(array $attributes): PreparationValidationFailure
    {
        return PreparationValidationFailure::query()->create($attributes);
    }
}
