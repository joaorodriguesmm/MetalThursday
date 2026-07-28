<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * Define os armazenamentos de cache utilizados pela aplicação.
 *
 * Os nomes das chaves, armazenamentos e drivers permanecem em inglês por
 * corresponderem aos contratos internos de configuração do Laravel.
 *
 * @return array<string, mixed> Configurações de cache.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Armazenamento predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'CACHE_STORE',
        'database',
    ),

    /*
    |--------------------------------------------------------------------------
    | Armazenamentos de cache
    |--------------------------------------------------------------------------
    */

    'stores' => [
        /*
         * Armazenamento temporário utilizado pelos testes automatizados.
         */
        'array' => [
            'driver' => 'array',

            'serialize' => false,
        ],

        /*
         * Armazenamento persistente utilizado pela aplicação.
         */
        'database' => [
            'driver' => 'database',

            'connection' => 'mysql',

            'table' => 'cache',

            'lock_connection' => 'mysql',

            'lock_table' => 'bloqueios_cache',
        ],

        /*
         * Armazenamento local de contingência para operações explicitamente
         * configuradas para utilizar ficheiros.
         */
        'file' => [
            'driver' => 'file',

            'path' => storage_path(
                'framework/cache/data',
            ),

            'lock_path' => storage_path(
                'framework/cache/data',
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefixo das chaves
    |--------------------------------------------------------------------------
    */

    'prefix' => env(
        'CACHE_PREFIX',
        Str::slug(
            (string) env(
                'APP_NAME',
                'MetalThursday',
            ),
        ).'-cache-',
    ),
];
