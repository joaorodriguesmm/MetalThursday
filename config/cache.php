<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * Define as configurações de cache da aplicação.
 *
 * Os nomes das chaves, armazenamentos e drivers permanecem em inglês por
 * corresponderem aos contratos de configuração utilizados pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
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
         * Armazenamento temporário mantido apenas em memória durante a
         * execução do processo atual.
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

            'connection' => env(
                'DB_CACHE_CONNECTION',
            ),

            'table' => env(
                'DB_CACHE_TABLE',
                'cache',
            ),

            'lock_connection' => env(
                'DB_CACHE_LOCK_CONNECTION',
            ),

            'lock_table' => env(
                'DB_CACHE_LOCK_TABLE',
            ),
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

        /*
         * Armazenamento distribuído através de Memcached.
         */
        'memcached' => [
            'driver' => 'memcached',

            'persistent_id' => env(
                'MEMCACHED_PERSISTENT_ID',
            ),

            'sasl' => [
                env(
                    'MEMCACHED_USERNAME',
                ),

                env(
                    'MEMCACHED_PASSWORD',
                ),
            ],

            'options' => [
            /*
                 * Exemplo:
                 *
                 * Memcached::OPT_CONNECT_TIMEOUT => 2000,
                 */],

            'servers' => [
                [
                    'host' => env(
                        'MEMCACHED_HOST',
                        '127.0.0.1',
                    ),

                    'port' => (int) env(
                        'MEMCACHED_PORT',
                        11211,
                    ),

                    'weight' => 100,
                ],
            ],
        ],

        /*
         * Armazenamento distribuído através de Redis.
         */
        'redis' => [
            'driver' => 'redis',

            'connection' => env(
                'REDIS_CACHE_CONNECTION',
                'cache',
            ),

            'lock_connection' => env(
                'REDIS_CACHE_LOCK_CONNECTION',
                'default',
            ),
        ],

        /*
         * Armazenamento distribuído através do Amazon DynamoDB.
         */
        'dynamodb' => [
            'driver' => 'dynamodb',

            'key' => env(
                'AWS_ACCESS_KEY_ID',
            ),

            'secret' => env(
                'AWS_SECRET_ACCESS_KEY',
            ),

            'region' => env(
                'AWS_DEFAULT_REGION',
                'us-east-1',
            ),

            'table' => env(
                'DYNAMODB_CACHE_TABLE',
                'cache',
            ),

            'endpoint' => env(
                'DYNAMODB_ENDPOINT',
            ),
        ],

        /*
         * Armazenamento em memória disponibilizado pelo Laravel Octane.
         */
        'octane' => [
            'driver' => 'octane',
        ],

        /*
         * Armazenamento de contingência.
         *
         * Tenta utilizar a base de dados e, caso essa operação falhe, utiliza
         * temporariamente o armazenamento em memória.
         */
        'failover' => [
            'driver' => 'failover',

            'stores' => [
                'database',
                'array',
            ],
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
                'laravel',
            ),
        ).'-cache-',
    ),
];
