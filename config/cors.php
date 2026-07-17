<?php

declare(strict_types=1);

$origins = array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost:8000'))),
));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values($origins),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With'],
    'exposed_headers' => ['X-Request-Id'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
