<?php

declare(strict_types=1);

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Define os canais de registo de eventos da aplicação.
 *
 * Os nomes das chaves e drivers permanecem em inglês por corresponderem aos
 * contratos internos de configuração do Laravel e do Monolog. Os nomes dos
 * canais definidos pelo MetalThursday utilizam português.
 *
 * @return array<string, mixed> Configurações de registo de eventos.
 *
 * @since 1.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Canal predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'LOG_CHANNEL',
        'pilha',
    ),

    /*
    |--------------------------------------------------------------------------
    | Registo de funcionalidades obsoletas
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env(
            'LOG_DEPRECATIONS_CHANNEL',
            'obsolescencias',
        ),

        'trace' => (bool) env(
            'LOG_DEPRECATIONS_TRACE',
            false,
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Canais de registo
    |--------------------------------------------------------------------------
    */

    'channels' => [
        /*
         * Agrega os canais definidos em LOG_STACK.
         */
        'pilha' => [
            'driver' => 'stack',

            'channels' => array_values(
                array_filter(
                    array_map(
                        static fn (
                            string $canal,
                        ): string => trim(
                            $canal,
                        ),
                        explode(
                            ',',
                            (string) env(
                                'LOG_STACK',
                                'diario',
                            ),
                        ),
                    ),
                    static fn (
                        string $canal,
                    ): bool => $canal !== '',
                ),
            ),

            'ignore_exceptions' => false,
        ],

        /*
         * Mantém todos os eventos num único ficheiro.
         */
        'unico' => [
            'driver' => 'single',

            'path' => storage_path(
                'logs/laravel.log',
            ),

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'replace_placeholders' => true,
        ],

        /*
         * Cria um ficheiro por dia e elimina os mais antigos após o período
         * de retenção configurado.
         */
        'diario' => [
            'driver' => 'daily',

            'path' => storage_path(
                'logs/laravel.log',
            ),

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'days' => (int) env(
                'LOG_DAILY_DAYS',
                14,
            ),

            'replace_placeholders' => true,
        ],

        /*
         * Regista separadamente avisos relacionados com funcionalidades
         * obsoletas.
         */
        'obsolescencias' => [
            'driver' => 'daily',

            'path' => storage_path(
                'logs/obsolescencias.log',
            ),

            'level' => 'notice',

            'days' => (int) env(
                'LOG_DAILY_DAYS',
                14,
            ),

            'replace_placeholders' => true,
        ],

        /*
         * Escreve no fluxo de erros padrão do processo.
         */
        'erro_padrao' => [
            'driver' => 'monolog',

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'handler' => StreamHandler::class,

            'handler_with' => [
                'stream' => 'php://stderr',
            ],

            'processors' => [
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
         * Descarta deliberadamente os eventos recebidos.
         */
        'nulo' => [
            'driver' => 'monolog',

            'handler' => NullHandler::class,
        ],

        /*
         * O nome `emergency` é um contrato interno do Laravel e não pode ser
         * traduzido.
         */
        'emergency' => [
            'path' => storage_path(
                'logs/laravel.log',
            ),
        ],
    ],
];
