<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'qdrant' => [
        'url' => env('QDRANT_URL', 'http://192.168.5.137:6333'),
        'collection' => env('QDRANT_COLLECTION', 'assistdoc_segments'),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://192.168.5.137:11434'),
        'generation_url' => env('OLLAMA_URL', 'http://192.168.5.137:11434') . '/api/generate',
        'embedding_url' => env('OLLAMA_URL', 'http://192.168.5.137:11434') . '/api/embed',
        'chat_model' => env('OLLAMA_CHAT_MODEL', 'llama3.2'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'mxbai-embed-large'),
        'embedding_dimensions' => (int) env('OLLAMA_EMBEDDING_DIMENSIONS', 1024),
        'embedding_max_input_chars' => (int) env('OLLAMA_EMBEDDING_MAX_INPUT_CHARS', 1800),
    ],

    'auth' => [
        'token_secret' => env('TOKEN_SECRET', 'assistdoc-dev-secret'),
    ],

];
