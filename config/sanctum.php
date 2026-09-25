<?php

declare(strict_types=1);

return [

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),

    'expiration' => null,

    'token_prefix' => '',

    'middleware' => [
        'authenticate_session' => \Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => \Illuminate\Cookie\Middleware\EncryptCookies::class,
        'verify_csrf_token' => \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ],

    'personal_access_token' => [
        'name' => env('APP_NAME', 'Saaspet') . ' Token',
        'expiration' => 1440, // 24 hours default
    ],

];