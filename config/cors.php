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

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
