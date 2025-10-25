<?php

return [
    'output' => function_exists('base_path')
        ? base_path('docs/api.md')
        : __DIR__ . '/../docs/api.md',

    'openapi_output' => function_exists('base_path')
        ? base_path('docs/api.json')
        : __DIR__ . '/../docs/api.json',

    'postman_output' => function_exists('base_path')
        ? base_path('docs/api.postman.json')
        : __DIR__ . '/../docs/api.postman.json',

    'redoc_output' => function_exists('base_path')
        ? base_path('docs/api.html')
        : __DIR__ . '/../docs/api.html',

    'enable_ai' => true,
    'ai' => [
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'api_key' => env('OPENAI_API_KEY'),
        'endpoint' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'timeout' => 15,
    ],

    'cache' => [
        'enabled' => true,
        'store_path' => function_exists('storage_path')
            ? storage_path('app/laravel-api-docx-cache.php')
            : __DIR__ . '/../storage/laravel-api-docx-cache.php',
    ],
];
