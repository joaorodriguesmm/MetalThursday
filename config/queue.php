<?php

declare(strict_types=1);

/**
 * Define as configurações das filas da aplicação.
 *
 * Os nomes das chaves, ligações e drivers permanecem em inglês por
 * corresponderem aos contratos internos de configuração do Laravel.
 *
 * @return array<string, mixed> Configurações das filas.
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
        'QUEUE_CONNECTION',
        'database',
    ),

    /*
    |--------------------------------------------------------------------------
    | Ligações de filas
    |--------------------------------------------------------------------------
    */

    'connections' => [
        /*
         * Executa o trabalho imediatamente no processo atual.
         *
         * Esta ligação é utilizada pelos testes automatizados.
         */
        'sync' => [
            'driver' => 'sync',
        ],

        /*
         * Persiste os trabalhos na base de dados para processamento
         * assíncrono.
         */
        'database' => [
            'driver' => 'database',

            'connection' => 'mysql',

            'table' => 'trabalhos_fila',

            'queue' => 'principal',

            'retry_after' => 90,

            'after_commit' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Processamento em lotes
    |--------------------------------------------------------------------------
    */

    'batching' => [
        'database' => 'mysql',

        'table' => 'lotes_trabalhos_fila',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trabalhos falhados
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'driver' => 'database-uuids',

        'database' => 'mysql',

        'table' => 'trabalhos_fila_falhados',
    ],
];
