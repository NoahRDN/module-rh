<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure here the cross-origin settings for API routes. Adjust
    | the allowed origins to match your frontend URLs.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('FRONTEND_URLS', env('FRONTEND_URL', 'http://localhost:5173')))
    )),

    // In local dev, it's common to access the frontend via a LAN IP (ex: http://192.168.x.x:5173).
    // When APP_ENV=local, allow typical private-network hosts via patterns to avoid CORS issues.
    'allowed_origins_patterns' => env('APP_ENV') === 'local'
        ? [
            '#^https?://localhost(:\\d+)?$#',
            '#^https?://127\\.0\\.0\\.1(:\\d+)?$#',
            '#^https?://10\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}(:\\d+)?$#',
            '#^https?://172\\.(1[6-9]|2\\d|3[0-1])\\.\\d{1,3}\\.\\d{1,3}(:\\d+)?$#',
            '#^https?://192\\.168\\.\\d{1,3}\\.\\d{1,3}(:\\d+)?$#',
        ]
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
