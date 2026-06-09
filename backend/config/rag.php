<?php

$profilePreset = env('RAG_PROFILE_PRESET', 'low_spec');

$profiles = [
        'default' => [
            'name' => 'Qwen',
            'slug' => 'qwen',

            'generation_model' => env('OLLAMA_GENERATION_MODEL', 'qwen3'),
            'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'qwen3-embedding'),

            'tokenizer_key' => 'qwen3',

            'token_window' => 40000,
            'embedding_dimensions' => (int) env('OLLAMA_EMBEDDING_DIMENSIONS', 4096),

            'available_for_new_runs' => true,
        ],

        'low_spec' => [
            'name' => env('OLLAMA_LOW_SPEC_NAME', 'Qwen PC Lenti'),
            'slug' => env('OLLAMA_LOW_SPEC_SLUG', 'qwen-pc-lenti'),

            'generation_model' => env('OLLAMA_LOW_SPEC_GENERATION_MODEL', 'qwen3:1.7b'),
            'embedding_model' => env('OLLAMA_LOW_SPEC_EMBEDDING_MODEL', 'qwen3-embedding:0.6b'),

            'tokenizer_key' => env('OLLAMA_LOW_SPEC_TOKENIZER_KEY', 'qwen3'),

            'token_window' => (int) env('OLLAMA_LOW_SPEC_TOKEN_WINDOW', 32000),
            'embedding_dimensions' => (int) env('OLLAMA_LOW_SPEC_EMBEDDING_DIMENSIONS', 1024),

            'available_for_new_runs' => true,
        ],
];

$defaultRetrievalProfile = $profiles[$profilePreset] ?? $profiles['default'];

return [
    'profile_preset' => $profilePreset,
    'profiles' => $profiles,
    'default_retrieval_profile' => $defaultRetrievalProfile,
    'text_ingestion_max_input_chars' => (int) env('RAG_TEXT_INGESTION_MAX_INPUT_CHARS', 1024 * 1024),
];
