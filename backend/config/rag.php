<?php

return [
    'default_retrieval_profile' => [
        'name' => 'Qwen',
        'slug' => 'qwen',
        'generation_model' => 'qwen3',
        'embedding_model' => 'qwen3-embedding',
        'tokenizer_key' => 'qwen3',
        'token_window' => 40000,
        'embedding_dimensions' => 4096,
        'available_for_new_runs' => true,
    ],
];
