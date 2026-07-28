<?php

declare(strict_types=1);

use Pdo\Mysql;

/**
 * Define a ligação MySQL utilizada pela aplicação.
 *
 * Os nomes das chaves e do driver permanecem em inglês por corresponderem
 * aos contratos internos de configuração do Laravel e do PDO.
 *
 * @return array<string, mixed> Configurações da base de dados.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Ligação predefinida
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'DB_CONNECTION',
        'mysql',
    ),

    /*
    |--------------------------------------------------------------------------
    | Ligações à base de dados
    |--------------------------------------------------------------------------
    */

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',

            'url' => env(
                'DB_URL',
            ),

            'host' => env(
                'DB_HOST',
                '127.0.0.1',
            ),

            'port' => env(
                'DB_PORT',
                '3306',
            ),

            'database' => env(
                'DB_DATABASE',
                'metalthursday',
            ),

            'username' => env(
                'DB_USERNAME',
                'root',
            ),

            'password' => env(
                'DB_PASSWORD',
                '',
            ),

            'unix_socket' => env(
                'DB_SOCKET',
                '',
            ),

            'charset' => env(
                'DB_CHARSET',
                'utf8mb4',
            ),

            'collation' => env(
                'DB_COLLATION',
                'utf8mb4_unicode_ci',
            ),

            'prefix' => '',

            'prefix_indexes' => true,

            'strict' => true,

            'engine' => null,

            'options' => extension_loaded(
                'pdo_mysql',
            )
                ? array_filter(
                    [
                        (
                            PHP_VERSION_ID >= 80500
                            ? Mysql::ATTR_SSL_CA
                            : PDO::MYSQL_ATTR_SSL_CA
                        ) => env(
                            'MYSQL_ATTR_SSL_CA',
                        ),
                    ],
                    static fn (
                        mixed $valor,
                    ): bool => $valor !== null,
                )
                : [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Repositório de migrations
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table' => 'migracoes',

        'update_date_on_publish' => true,
    ],
];
