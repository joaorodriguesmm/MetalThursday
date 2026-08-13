<?php

declare(strict_types=1);

/**
 * Define as filas utilizadas pela aplicação.
 *
 * Os nomes das chaves, ligações e drivers permanecem em inglês por
 * corresponderem aos contratos de configuração utilizados pelo Laravel.
 *
 * @return array<string, mixed> Configuração das filas.
 *
 * @since 1.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Ligação predefinida
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'QUEUE_CONNECTION',
        'database',
    ),

    /*
    |--------------------------------------------------------------------------
    | Ligações disponíveis
    |--------------------------------------------------------------------------
    */

    'connections' => [
        /*
         * Executa o trabalho imediatamente no processo atual.
         */
        'sync' => [
            'driver' => 'sync',
        ],

        /*
         * Persiste os trabalhos na base de dados.
         */
        'database' => [
            'driver' => 'database',

            'connection' => env(
                'DB_CONNECTION',
            ),

            'table' => 'trabalhos_fila',

            'queue' => env(
                'DB_QUEUE',
                'principal',
            ),

            'retry_after' => (int) env(
                'DB_QUEUE_RETRY_AFTER',
                90,
            ),

            'after_commit' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Processamento em lotes
    |--------------------------------------------------------------------------
    */

    'batching' => [
        'database' => env(
            'DB_CONNECTION',
        ),

        'table' => 'lotes_trabalhos_fila',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trabalhos falhados
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'driver' => env(
            'QUEUE_FAILED_DRIVER',
            'database-uuids',
        ),

        'database' => env(
            'DB_CONNECTION',
        ),

        'table' => 'trabalhos_fila_falhados',
    ],
];
