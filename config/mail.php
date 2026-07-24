<?php

declare(strict_types=1);

/**
 * Define as configurações de envio de correio eletrónico da aplicação.
 *
 * Os nomes das chaves, mailers e transports permanecem em inglês por
 * corresponderem aos contratos de configuração utilizados pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Mailer predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'MAIL_MAILER',
        'log',
    ),

    /*
    |--------------------------------------------------------------------------
    | Mailers disponíveis
    |--------------------------------------------------------------------------
    */

    'mailers' => [
        /*
         * Envio através de um servidor SMTP.
         */
        'smtp' => [
            'transport' => 'smtp',

            'scheme' => env(
                'MAIL_SCHEME',
            ),

            'url' => env(
                'MAIL_URL',
            ),

            'host' => env(
                'MAIL_HOST',
                '127.0.0.1',
            ),

            'port' => (int) env(
                'MAIL_PORT',
                2525,
            ),

            'username' => env(
                'MAIL_USERNAME',
            ),

            'password' => env(
                'MAIL_PASSWORD',
            ),

            'timeout' => null,

            'local_domain' => env(
                'MAIL_EHLO_DOMAIN',
                parse_url(
                    (string) env(
                        'APP_URL',
                        'http://localhost',
                    ),
                    PHP_URL_HOST,
                ) ?: 'localhost',
            ),
        ],

        /*
         * Envio através do Amazon SES.
         */
        'ses' => [
            'transport' => 'ses',
        ],

        /*
         * Envio através do Postmark.
         */
        'postmark' => [
            'transport' => 'postmark',
        ],

        /*
         * Envio através do Resend.
         */
        'resend' => [
            'transport' => 'resend',
        ],

        /*
         * Envio através do executável sendmail do sistema.
         */
        'sendmail' => [
            'transport' => 'sendmail',

            'path' => env(
                'MAIL_SENDMAIL_PATH',
                '/usr/sbin/sendmail -bs -i',
            ),
        ],

        /*
         * Escreve as mensagens no sistema de registos da aplicação.
         */
        'log' => [
            'transport' => 'log',

            'channel' => env(
                'MAIL_LOG_CHANNEL',
            ),
        ],

        /*
         * Mantém as mensagens em memória durante o processo atual.
         *
         * É especialmente útil para testes automatizados.
         */
        'array' => [
            'transport' => 'array',
        ],

        /*
         * Tenta os mailers indicados sequencialmente até conseguir enviar.
         */
        'failover' => [
            'transport' => 'failover',

            'mailers' => [
                'smtp',
                'log',
            ],

            'retry_after' => (int) env(
                'MAIL_FAILOVER_RETRY_AFTER',
                60,
            ),
        ],

        /*
         * Distribui as mensagens alternadamente pelos mailers indicados.
         */
        'roundrobin' => [
            'transport' => 'roundrobin',

            'mailers' => [
                'ses',
                'postmark',
            ],

            'retry_after' => (int) env(
                'MAIL_ROUNDROBIN_RETRY_AFTER',
                60,
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remetente global
    |--------------------------------------------------------------------------
    */

    'from' => [
        'address' => env(
            'MAIL_FROM_ADDRESS',
            'nao-responder@metalthursday.local',
        ),

        'name' => env(
            'MAIL_FROM_NAME',
            env(
                'APP_NAME',
                'MetalThursday',
            ),
        ),
    ],
];
