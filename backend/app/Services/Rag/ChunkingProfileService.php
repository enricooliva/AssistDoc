<?php

namespace App\Services\Rag;

use App\Models\ChunkingProfile;
use App\Repositories\ChunkingProfileRepository;

class ChunkingProfileService
{
    public function __construct(private readonly ChunkingProfileRepository $repository)
    {
    }

    public function list(): array
    {
        return array_map(fn (ChunkingProfile $profile): array => [
            'id' => (string) $profile->id,
            'name' => $profile->name,
            'slug' => $profile->slug,
            'chunkSizeTokens' => $profile->chunk_size_tokens,
            'overlapTokens' => $profile->overlap_tokens,
            'active' => $profile->active,
            'notes' => $profile->notes,
        ], $this->repository->listAll());
    }

    public function resolve(?string $chunkingProfileId = null): ChunkingProfile
    {
        if ($chunkingProfileId === null || $chunkingProfileId === '') {
            throw new \RuntimeException('È necessario selezionare un profilo di segmentazione.');
        }

        $profile = $this->repository->find($chunkingProfileId);

        if (! $profile) {
            throw new \RuntimeException('Nessun profilo di segmentazione disponibile.');
        }

        if (! $profile->active) {
            throw new \RuntimeException('Il profilo di segmentazione selezionato non è attivo.');
        }

        return $profile;
    }

    public function defaultProfiles(): array
    {
        $profiles = $this->repository->listAll();
        $preferredSlugs = ['medium']; //['small', 'medium', 'large'];
        $preferred = [];

        foreach ($preferredSlugs as $slug) {
            foreach ($profiles as $profile) {
                if ($profile->slug === $slug && $profile->active) {
                    $preferred[] = $profile;
                }
            }
        }

        if ($preferred !== []) {
            return $preferred;
        }

        return array_values(array_filter($profiles, static fn (ChunkingProfile $profile): bool => $profile->active));
    }

    public function validate(int $chunkSize, int $overlap): void
    {
        if ($chunkSize <= 0 || $overlap < 0 || $overlap >= $chunkSize) {
            throw new \RuntimeException('Il profilo di segmentazione richiede un overlap inferiore alla dimensione del chunk.');
        }
    }
}
