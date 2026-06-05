<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\AI\EmbeddingService;
use Illuminate\Support\Facades\Log;
use App\Contratto;
use Illuminate\Support\Str;

class QdrantService
{
    protected string $url;
    public string $platform;
    protected string $collectionName;
    protected int $vectorSize;
    public EmbeddingService $embeddingService;

    public function __construct(
        string $prefixName = 'documenti',
        string $platform = 'AssistDoc',
        //int $vectorSize = 384
        int $vectorSize = 1024        
    ) {
        $this->url = (string) config('services.qdrant.url', env('QDRANT_URL', 'http://192.168.5.137:6333'));
        $this->embeddingService = new EmbeddingService();
         // Normalize platform
        $this->platform = $platform;

        // Build collection name: e.g. documenti_unicollstud
        $this->collectionName = (string) config('services.qdrant.collection', 'assistdoc_segments');

        $this->vectorSize = $this->embeddingService->getEmbeddingDimensions();
    }

    public function getCollection(string $name = null, ?int $vectorSize = null): array
    {
        $name = $this->resolveCollectionName($name, $vectorSize);

        return Http::get("{$this->url}/collections/{$name}")->json();
    }
     
        /**
     * Get a point from Qdrant by collection and point ID.
     *
     * @param string $collection Collection name
     * @param int|string $pointId Point ID
     * @return array|null Returns the point data if found, or null if not found
     */
    public function getPoint($pointId, string $collection = null, ?int $vectorSize = null)
    {
        $collection = $this->resolveCollectionName($collection, $vectorSize);

        try {          
            $this->ensureCollectionExists($collection, $vectorSize);

            $response = Http::get("{$this->url}/collections/{$collection}/points/{$pointId}");

            if ($response->successful()) {
                $data = $response->json();

                // Qdrant returns "result": { "id": ..., "vector": ..., "payload": ... }
                return $data['result'] ?? null;
            }

            return null; // Not found or other HTTP error
        } catch (\Exception $e) {
            // Optional: log error
            // Log::warning("Qdrant getPoint error: {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Create a collection in Qdrant
     */
    public function createCollection(string $name = null, int $vectorSize = null): array
    {
        $name = $this->resolveCollectionName($name, $vectorSize);
        $vectorSize = $vectorSize ?? $this->vectorSize;
    
        return Http::put("{$this->url}/collections/{$name}", [
            'vectors' => [
                'size' => $vectorSize,
                'distance' => 'Cosine',
            ],
        ])->json();
    }

    /**
     * Create indexes
     */
    public function createIndexes(string $collection)
    {
        $fields = [
            'attachment_id',
            'contratto_id',
            'user_id'
        ];

        foreach ($fields as $field) {
            Http::post("{$this->url}/collections/{$collection}/index", [
                'field_name' => $field,
                'field_schema' => 'keyword'   // perfect for IDs
            ]);
        }

        return "Indexes created successfully.";
    }

    /**
     * Upsert points into collection
     */
    public function upsert(array $points, string $name = null, ?int $vectorSize = null): array
    {
        $name = $this->resolveCollectionName($name, $vectorSize);
        $this->ensureCollectionExists($name, $vectorSize);

        return Http::put("{$this->url}/collections/{$name}/points", [
            'points' => $points,
        ])->json();
    }

    public function scroll(array $params, string $collection = null, ?int $vectorSize = null): array
    {
        $collection = $this->resolveCollectionName($collection, $vectorSize);
        $this->ensureCollectionExists($collection, $vectorSize);

        return Http::post("{$this->url}/collections/{$collection}/points/scroll", $params)
            ->json();
    }

    /**
     * Remove and recreate a collection so it starts empty.
     */
    public function reset(string $collection = null, ?int $vectorSize = null): array
    {
        $collection = $this->resolveCollectionName($collection, $vectorSize);
        $vectorSize = $vectorSize ?? $this->vectorSize;

        try {
            $response = Http::delete("{$this->url}/collections/{$collection}");

            if (! $response->successful() && $response->status() !== 404) {
                Log::warning("Qdrant reset returned status {$response->status()} for collection {$collection}");
            }
        } catch (\Throwable $e) {
            Log::error("Errore Qdrant reset delete phase: {$e->getMessage()}", [
                'collection' => $collection,
            ]);
        }

        $created = $this->createCollection($collection, $vectorSize);
        $this->createIndexes($collection);

        return [
            'collection' => $collection,
            'vector_size' => $vectorSize,
            'created' => $created,
        ];
    }

    /**
     * Remove all points from an existing collection without recreating it.
     */
    public function clear(string $collection = null, ?int $vectorSize = null, int $batchSize = 512): array
    {
        $collection = $this->resolveCollectionName($collection, $vectorSize);
        $deleted = 0;
        $offset = null;

        try {
            $exists = Http::get("{$this->url}/collections/{$collection}");

            if ($exists->status() === 404) {
                return [
                    'collection' => $collection,
                    'deleted' => 0,
                    'chunks' => 0,
                ];
            }

            if (! $exists->successful()) {
                Log::warning("Qdrant clear lookup returned status {$exists->status()} for collection {$collection}");

                return [
                    'collection' => $collection,
                    'deleted' => 0,
                    'chunks' => 0,
                ];
            }

            do {
                $response = Http::post("{$this->url}/collections/{$collection}/points/scroll", [
                    'limit' => $batchSize,
                    'offset' => $offset,
                    'with_payload' => false,
                    'with_vector' => false,
                ]);

                if (! $response->successful()) {
                    Log::warning("Qdrant clear scroll returned status {$response->status()} for collection {$collection}");

                    break;
                }

                $result = $response->json('result', []);
                $points = $result['points'] ?? [];
                $offset = $result['next_page_offset'] ?? null;

                $pointIds = array_values(array_filter(array_map(static fn (array $point) => $point['id'] ?? null, $points), static fn ($id) => $id !== null && $id !== ''));

                if ($pointIds !== []) {
                    $deleteResponse = Http::post("{$this->url}/collections/{$collection}/points/delete", [
                        'points' => $pointIds,
                    ]);

                    if (! $deleteResponse->successful()) {
                        Log::warning("Qdrant clear delete returned status {$deleteResponse->status()} for collection {$collection}");

                        break;
                    }

                    $deleted += count($pointIds);
                }
            } while ($offset !== null);
        } catch (\Throwable $e) {
            Log::error("Errore Qdrant clear: {$e->getMessage()}", [
                'collection' => $collection,
            ]);
        }

        return [
            'collection' => $collection,
            'deleted' => $deleted,
            'chunks' => $deleted > 0 ? (int) ceil($deleted / $batchSize) : 0,
        ];
    }
   

    protected function enrichQuery(string $query): string
    {
        return "Domanda utente: {$query}. Trova nel testo informazioni pertinenti, anche se espresse in modo diverso.";
    }
    /**
     * Search by text with optional document_type filter
     */
    public function searchByText(
        string $text,
        int $limit = 5,
        ?string $documentType = null,
        array $rules = [],
        string $name = null,
        ?int $vectorSize = null
    ): array
    {
        $vector = $this->embeddingService->generateEmbedding($text);
        return $this->search($vector, $limit, $documentType, $rules, $name, $vectorSize);
    }


    // public function fileExists(string $collection, string $attachmentId): bool
    // {
    //     $response = Http::post("{$this->url}/collections/{$collection}/points/scroll", [
    //         'limit' => 1,
    //         'with_payload' => false,
    //         'filter' => [
    //             'must' => [
    //                 [
    //                     'key' => 'attachment_id',
    //                     'match' => ['value' => $attachmentId]
    //                 ]
    //             ]
    //         ]
    //     ])->json();

    //     return !empty($response['points']);
    // }
    
    /**
     * Search by vector with platform filter
     */
    public function search(
        array $vector,
        int $limit = 5,
        string $documentType = null,
        array $rules = [],
        string $name = null,
        ?int $vectorSize = null
    ): array
    {
        $name = $this->resolveCollectionName($name, $vectorSize);
        $this->ensureCollectionExists($name, $vectorSize);

        $filter = ['must' => [
            // [
            //     'key' => 'platform',
            //     'match' => ['value' => $this->platform]
            // ]
        ]];

        if ($documentType) {
            $filter['must'][] = [
                'key' => 'document_type',
                'match' => ['value' => $documentType]
            ];
        }

        // Convert rules into Qdrant filter format
        $extra = $this->rulesToQdrantFilter($rules);

        // Merge filters (must, should, must_not etc.)
        foreach ($extra as $key => $value) {

            // If the key does not exist in $filter, create it
            if (!isset($filter[$key])) {
                $filter[$key] = [];
            }

            // Normalize both sides into arrays
            $existing = (array)$filter[$key];
            $value = (array)$value;

            // Merge rule blocks into filter
            $filter[$key] = array_merge($existing, $value);
        }

        Log::info('Qdrant search filter: ' . json_encode($filter, JSON_PRETTY_PRINT));


        return Http::post("{$this->url}/collections/{$name}/points/search", [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
            'filter' => $filter
        ])->json();
    }

    /**
     * Elimina punti in una collezione usando un filtro Qdrant.
     *
     * @param string $collection Nome della collezione
     * @param array $filter Filtro Qdrant in formato "must/match"
     * @return void
     */
    public function deleteByFilter(array $filter, string $collection = null, ?int $vectorSize = null): void
    {
        $collection = $this->resolveCollectionName($collection, $vectorSize);
        try {
            $this->ensureCollectionExists($collection, $vectorSize);
            
            // $filter['must'][]= [
            //     // 'key' => 'platform',
            //     // 'match' => ['value' => $this->platform]
            // ];

            $response = Http::post("{$this->url}/collections/{$collection}/points/delete", [                
                'filter' => $filter,                
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::warning("Qdrant deleteByFilter returned status {$response->getStatusCode()} for collection {$collection}");
            }
        } catch (\Exception $e) {
            Log::error("Errore Qdrant deleteByFilter: " . $e->getMessage());
        }
    }

    public function payloadExists(string $payloadKey, mixed $payloadValue, string $collection = null): bool {
        $filter = [
            'must' => [
                [
                    'key' => 'platform',
                    'match' => ['value' => $this->platform]
                ],
                [
                    'key' => $payloadKey,
                    'match' => [ 'value' => $payloadValue]
                ]
            ]
        ];
    
        $response = $this->scroll([
            'filter' => $filter,
            'limit' => 1,
            'with_vector' => false,
            'with_payload' => false,
        ], $collection);
    
        return !empty($response['result']['points']);
    }
    
    public function attachmentExists(int $attachmentId, string $collection = null): bool
    {
        return $this->payloadExists('attachment_id', $attachmentId, $collection);
    }

    private function resolveCollectionName(?string $name = null, ?int $vectorSize = null): string
    {
        $baseName = $name !== null && $name !== '' ? $name : $this->collectionName;

        if ($vectorSize === null) {
            return $baseName;
        }

        $suffix = '_'.$vectorSize;

        return Str::endsWith($baseName, $suffix) ? $baseName : $baseName.$suffix;
    }

    private function ensureCollectionExists(string $name, ?int $vectorSize = null): void
    {
        try {
            $response = Http::get("{$this->url}/collections/{$name}");

            if ($response->successful()) {
                return;
            }

            if ($response->status() !== 404) {
                Log::warning("Qdrant collection lookup returned status {$response->status()} for {$name}");

                return;
            }

            $created = $this->createCollection($name, $vectorSize ?? $this->vectorSize);
            Log::info('Qdrant collection created', [
                'collection' => $name,
                'response' => $created,
            ]);
        } catch (\Throwable $e) {
            Log::error("Errore Qdrant ensureCollectionExists: {$e->getMessage()}", [
                'collection' => $name,
            ]);
        }
    }

    private function rulesToQdrantFilter(array $rules): array
    {
        $must = [];
        $must_not = [];

        foreach ($rules as $rule) {

            // Safety checks
            if (!isset($rule['field'], $rule['operator'], $rule['value'])) {
                continue;
            }

            $field = $rule['field'];
            $op    = strtolower(trim($rule['operator']));
            $value = $rule['value'];

            switch ($op) {

                case '=':
                case 'eq':
                case 'equals':
                    $must[] = [
                        'key' => $field,
                        'match' => [ 'value' => $value ]
                    ];
                    break;

                case '!=':
                case '<>':
                case 'neq':
                case 'not equals':
                    $must_not[] = [
                        'key' => $field,
                        'match' => [ 'value' => $value ]
                    ];
                    break;

                case '>':
                    $must[] = [
                        'key' => $field,
                        'range' => [ 'gt' => $value ]
                    ];
                    break;

                case '>=':
                    $must[] = [
                        'key' => $field,
                        'range' => [ 'gte' => $value ]
                    ];
                    break;

                case '<':
                    $must[] = [
                        'key' => $field,
                        'range' => [ 'lt' => $value ]
                    ];
                    break;

                case '<=':
                    $must[] = [
                        'key' => $field,
                        'range' => [ 'lte' => $value ]
                    ];
                    break;

                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $must[] = [
                            'key' => $field,
                            'range' => [
                                'gte' => $value[0],
                                'lte' => $value[1]
                            ]
                        ];
                    }
                    break;

                case 'in':
                    if (is_array($value)) {
                        $must[] = [
                            'key' => $field,
                            'match' => [ 'any' => $value ] // Qdrant ANY match
                        ];
                    }
                    break;

                case 'not in':
                    if (is_array($value)) {
                        $must_not[] = [
                            'key' => $field,
                            'match' => [ 'any' => $value ]
                        ];
                    }
                    break;

                default:
                    // fallback to equals
                    $must[] = [
                        'key' => $field,
                        'match' => [ 'value' => $value ]
                    ];
            }
        }

        $filter = [];
        if (count($must))     $filter['must'] = $must;
        if (count($must_not)) $filter['must_not'] = $must_not;

        return $filter;
    }

    /**
     * Build the base payload for any entity
     *
     * @param string $documentType Document type: 'contract', 'attachment', 'storyprocess'
     * @param Contratto $contratto Related contract
     * @param array $extra Additional fields specific to entity
     * @return array
     */
    public function buildPayload(string $documentType, Contratto $contratto, array $extra = []): array
    {
        $payload = [
            'platform' => $this->platform,
            'document_type' => $documentType,
            'contratto_id' => $contratto->id,
            'user_id' => $contratto->user_id,
            'user_name' => $contratto->user->name ?? null,
        ];

        return array_merge($payload, $extra);
    }

    /**
     * Generate a deterministic point ID for Qdrant.
     *
     * @param string $type       Type of point: 'contract', 'storyprocess', 'attachment', 'free_text', 'ui_page'
     * @param int    $entityId   ID of the entity (contract_id, attachment_id, storyprocess_id, user_id)
     * @param int    $chunkIndex Optional chunk index for attachments or free-text
     * @param string $chunkLevel Optional chunk level: 'chunk_800', 'chunk_1600', 'chunk_3200'
     * @param int    $extra      Optional offset for storyprocess or free-text sequence
     * @return int
     */
    // public function generatePointId(string $type, int $entityId, string $level = '', int $chunkIndex = 0): int
    // {
    //     // Namespaces for different point types
    //     $base = match($type) {
    //         'contract'     => 1_000_000_000,
    //         'storyprocess' => 2_000_000_000,
    //         'attachment'   => 3_000_000_000,
    //         'free_text'    => 4_000_000_000,
    //         default        => 9_000_000_000,
    //     };

    //     // Level offsets for chunk sizes (for attachments)
    //     $levelOffset = match($level) {
    //         'chunk_800'  => 0,
    //         'chunk_1600' => 100_000,
    //         'chunk_3200' => 200_000,
    //         default      => 0,
    //     };

    //     return $base + ($entityId * 1_000) + $levelOffset + $chunkIndex;
    // }

    public function generatePointId(string $entity, int|string $id, string $level = '', int $chunkIndex = 0): int
    {
        return abs(crc32(implode(':', [$entity, $id, $level, $chunkIndex])));
    }


}
