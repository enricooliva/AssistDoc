<?php

return [
    'default_retrieval_profile' => [
        'name' => 'Qwen',
        'slug' => 'qwen',

        'generation_model' => env('OLLAMA_GENERATION_MODEL', 'qwen3'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'qwen3-embedding'),

        'tokenizer_key' => 'qwen3',

        'token_window' => 40000,
        'embedding_dimensions' => (int) env('OLLAMA_EMBEDDING_DIMENSIONS', 4096),

        'available_for_new_runs' => true,
    ],
];
