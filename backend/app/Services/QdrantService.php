<?php

namespace App\Services;

class QdrantService
{
    /**
     * @var array<string, array<string, array{id:mixed, vector: array<int, float|int>, payload: array<string, mixed>}>> 
     */
    private static array $collections = [];

    public function upsert(array $points, string $collection = 'documents'): void
    {
        $stored = $this->getCollection($collection);

        foreach ($points as $point) {
            $stored[(string) $point['id']] = [
                'id' => $point['id'],
                'vector' => $point['vector'],
                'payload' => $point['payload'],
            ];
        }

        $this->putCollection($collection, $stored);
    }

    public function deleteByFilter(array $filter, string $collection = 'documents'): void
    {
        $stored = $this->getCollection($collection);
        $remaining = [];

        foreach ($stored as $point) {
            if (! $this->matchesFilter($point['payload'], $filter)) {
                $remaining[(string) $point['id']] = $point;
            }
        }

        $this->putCollection($collection, $remaining);
    }

    public function payloadExists(string $key, mixed $value, string $collection = 'documents'): bool
    {
        foreach ($this->getCollection($collection) as $point) {
            if (($point['payload'][$key] ?? null) === $value) {
                return true;
            }
        }

        return false;
    }

    public function reset(string $collection = 'documents'): void
    {
        unset(self::$collections[$collection]);
    }

    public function search(string $collection, array $vector, array $filter = [], int $limit = 10): array
    {
        $matches = [];

        foreach ($this->getCollection($collection) as $point) {
            if (! $this->matchesFilter($point['payload'], $filter)) {
                continue;
            }

            $matches[] = [
                'id' => $point['id'],
                'score' => $this->cosineSimilarity($vector, $point['vector']),
                'payload' => $point['payload'],
            ];
        }

        usort($matches, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_slice($matches, 0, $limit);
    }

    public function generatePointId(string $entity, int|string $id, string $level = '', int $chunkIndex = 0): int
    {
        return abs(crc32(implode(':', [$entity, $id, $level, $chunkIndex])));
    }

    private function cosineSimilarity(array $left, array $right): float
    {
        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;
        $size = min(count($left), count($right));

        for ($index = 0; $index < $size; $index++) {
            $dot += $left[$index] * $right[$index];
            $leftMagnitude += $left[$index] ** 2;
            $rightMagnitude += $right[$index] ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }

    private function matchesFilter(array $payload, array $filter): bool
    {
        $clauses = $filter['must'] ?? [];

        foreach ($clauses as $clause) {
            $key = $clause['key'] ?? null;
            $expected = $clause['match']['value'] ?? null;

            if ($key === null || ($payload[$key] ?? null) !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function getCollection(string $collection): array
    {
        return self::$collections[$collection] ?? [];
    }

    private function putCollection(string $collection, array $points): void
    {
        self::$collections[$collection] = $points;
    }
}
