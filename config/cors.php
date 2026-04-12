<?php

$frontendUrl = trim((string) env('FRONTEND_URL', ''));
$extraOrigins = array_filter(array_map(
    static fn ($value) => trim((string) $value),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
));

$allowedOrigins = array_values(array_unique(array_filter([
    'http://localhost:5173',
    $frontendUrl !== '' ? rtrim($frontendUrl, '/') : null,
    ...$extraOrigins,
])));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'index.php/api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
