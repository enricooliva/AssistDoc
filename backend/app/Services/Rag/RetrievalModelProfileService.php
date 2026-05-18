<?php

namespace App\Services\Rag;

use App\Models\RetrievalModelProfile;
use App\Repositories\RetrievalModelProfileRepository;

class RetrievalModelProfileService
{
    public function __construct(private readonly RetrievalModelProfileRepository $repository)
    {
    }

    public function defaultAvailable(): RetrievalModelProfile
    {
        $slug = (string) config('rag.default_retrieval_profile.slug', 'qwen');
        $profile = $this->repository->findBySlug($slug);

        if (! $profile) {
            throw new \RuntimeException('Nessun profilo di retrieval disponibile.');
        }

        return $profile;
    }

    public function resolve(?string $profileId = null): RetrievalModelProfile
    {
        if ($profileId === null || $profileId === '') {
            return $this->defaultAvailable();
        }

        $profile = $this->repository->findBySlug($profileId) ?? $this->repository->find($profileId);

        if (! $profile) {
            return $this->defaultAvailable();
        }

        return $profile;
    }
}
