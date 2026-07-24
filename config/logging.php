<?php

declare(strict_types=1);

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Define as configurações de registo de eventos da aplicação.
 *
 * Os nomes das chaves, canais e drivers permanecem em inglês por
 * corresponderem aos contratos de configuração utilizados pelo Laravel
 * e pelo Monolog.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Canal predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'LOG_CHANNEL',
        'stack',
    ),

    /*
    |--------------------------------------------------------------------------
    | Registo de funcionalidades obsoletas
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env(
            'LOG_DEPRECATIONS_CHANNEL',
            'null',
        ),

        'trace' => env(
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
         * Agrega vários canais num único canal lógico.
         */
        'stack' => [
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
                                'single',
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
         * Mantém todos os registos num único ficheiro.
         */
        'single' => [
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
         * Cria um ficheiro de registo por dia.
         */
        'daily' => [
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
         * Envia eventos críticos para um canal Slack.
         */
        'slack' => [
            'driver' => 'slack',

            'url' => env(
                'LOG_SLACK_WEBHOOK_URL',
            ),

            'username' => env(
                'LOG_SLACK_USERNAME',
                env(
                    'APP_NAME',
                    'MetalThursday',
                ),
            ),

            'emoji' => env(
                'LOG_SLACK_EMOJI',
                ':boom:',
            ),

            'level' => env(
                'LOG_LEVEL',
                'critical',
            ),

            'replace_placeholders' => true,
        ],

        /*
         * Envia os registos para o serviço Papertrail.
         */
        'papertrail' => [
            'driver' => 'monolog',

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'handler' => env(
                'LOG_PAPERTRAIL_HANDLER',
                SyslogUdpHandler::class,
            ),

            'handler_with' => [
                'host' => env(
                    'PAPERTRAIL_URL',
                ),

                'port' => env(
                    'PAPERTRAIL_PORT',
                ),

                'connectionString' => 'tls://'
                    .env(
                        'PAPERTRAIL_URL',
                    )
                    .':'
                    .env(
                        'PAPERTRAIL_PORT',
                    ),
            ],

            'processors' => [
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
         * Escreve os registos no fluxo de erros padrão.
         */
        'stderr' => [
            'driver' => 'monolog',

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'handler' => StreamHandler::class,

            'handler_with' => [
                'stream' => 'php://stderr',
            ],

            'formatter' => env(
                'LOG_STDERR_FORMATTER',
            ),

            'processors' => [
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
         * Escreve os registos no serviço syslog do sistema.
         */
        'syslog' => [
            'driver' => 'syslog',

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'facility' => env(
                'LOG_SYSLOG_FACILITY',
                LOG_USER,
            ),

            'replace_placeholders' => true,
        ],

        /*
         * Escreve através do mecanismo error_log do PHP.
         */
        'errorlog' => [
            'driver' => 'errorlog',

            'level' => env(
                'LOG_LEVEL',
                'debug',
            ),

            'replace_placeholders' => true,
        ],

        /*
         * Descarta intencionalmente todos os eventos recebidos.
         */
        'null' => [
            'driver' => 'monolog',

            'handler' => NullHandler::class,
        ],

        /*
         * Canal utilizado quando o sistema de registo principal falha.
         */
        'emergency' => [
            'path' => storage_path(
                'logs/laravel.log',
            ),
        ],
    ],
];
