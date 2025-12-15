<?php

return [
    'stateful' => false,
    'guard' => ['web'], // Mudando de 'api' para 'web' para evitar conflito
    'prefix' => 'api',
    'domain' => null,
    'expiration' => null,
    'middleware' => [
        'encrypt_cookies' => \Illuminate\Cookie\Middleware\EncryptCookies::class,
    ],
];
