<?php

return [

    'paths' => [
        'api/*',
        'login',
        'logout',
        'signup',
        'register',
        'forgot-password',
        'reset-password',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://www.ofuq.academy',
        'https://ofuq.academy',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
