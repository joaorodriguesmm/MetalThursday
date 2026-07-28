<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * Define os armazenamentos de cache utilizados pela aplicação.
 *
 * Os nomes das chaves, armazenamentos e drivers permanecem em inglês por
 * corresponderem aos contratos de configuração utilizados pelo Laravel.
 *
 * @return array<string, mixed> Configuração do cache.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
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
    | Armazenamentos disponíveis
    |--------------------------------------------------------------------------
    */

    'stores' => [
        /*
         * Armazenamento temporário mantido apenas durante o processo atual.
         */
        'array' => [
            'driver' => 'array',

            'serialize' => false,
        ],

        /*
         * Armazenamento persistente na base de dados.
         */
        'database' => [
            'driver' => 'database',

            'connection' => 'mariadb',

            'table' => 'cache',

            'lock_connection' => 'mariadb',

            'lock_table' => 'bloqueios_cache',
        ],

        /*
         * Armazenamento persistente no sistema de ficheiros.
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
