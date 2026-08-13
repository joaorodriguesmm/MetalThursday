<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * Define as sessões utilizadas pela aplicação.
 *
 * Os nomes das chaves e drivers permanecem em inglês por corresponderem aos
 * contratos de configuração utilizados pelo Laravel.
 *
 * @return array<string, mixed> Configuração das sessões.
 *
 * @since 1.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Driver de sessão
    |--------------------------------------------------------------------------
    */

    'driver' => env(
        'SESSION_DRIVER',
        'database',
    ),

    /*
    |--------------------------------------------------------------------------
    | Duração da sessão
    |--------------------------------------------------------------------------
    */

    'lifetime' => (int) env(
        'SESSION_LIFETIME',
        120,
    ),

    'expire_on_close' => (bool) env(
        'SESSION_EXPIRE_ON_CLOSE',
        false,
    ),

    /*
    |--------------------------------------------------------------------------
    | Encriptação
    |--------------------------------------------------------------------------
    */

    'encrypt' => (bool) env(
        'SESSION_ENCRYPT',
        true,
    ),

    /*
    |--------------------------------------------------------------------------
    | Armazenamento
    |--------------------------------------------------------------------------
    */

    'files' => storage_path(
        'framework/sessions',
    ),

    'connection' => env(
        'DB_CONNECTION',
    ),

    'table' => 'sessoes',

    'store' => null,

    /*
    |--------------------------------------------------------------------------
    | Limpeza de sessões expiradas
    |--------------------------------------------------------------------------
    */

    'lottery' => [
        2,
        100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie da sessão
    |--------------------------------------------------------------------------
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(
            (string) env(
                'APP_NAME',
                'MetalThursday',
            ),
        ).'-sessao',
    ),

    'path' => env(
        'SESSION_PATH',
        '/',
    ),

    'domain' => env(
        'SESSION_DOMAIN',
    ),

    'secure' => (bool) env(
        'SESSION_SECURE_COOKIE',
        false,
    ),

    'http_only' => (bool) env(
        'SESSION_HTTP_ONLY',
        true,
    ),

    'same_site' => env(
        'SESSION_SAME_SITE',
        'lax',
    ),

    'partitioned' => (bool) env(
        'SESSION_PARTITIONED_COOKIE',
        false,
    ),
];
