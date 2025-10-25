<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. For token-only APIs, leave this empty so
    | Sanctum does not treat any domain as stateful.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | Sanctum will attempt to authenticate using these guards first. If none
    | succeed, Sanctum will use the bearer token on the request.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Sanctum Route prefix
    |--------------------------------------------------------------------------
    |
    | Controls the prefix for Sanctum routes. Keeping 'api' is fine.
    |
    */

    'prefix' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | Token expiration in minutes (null for no automatic expiration).
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Optional token prefix for secret scanning support.
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware used for first-party SPA cookie auth. Unchanged; not used
    | when stateful domains are empty and API group does not include
    | EnsureFrontendRequestsAreStateful.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
