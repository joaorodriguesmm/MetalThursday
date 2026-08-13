<?php

declare(strict_types=1);

/**
 * Define os meios de envio de correio eletrónico da aplicação.
 *
 * Os nomes das chaves e transports permanecem em inglês por corresponderem
 * aos contratos internos de configuração do Laravel e do Symfony Mailer. Os
 * nomes dos mailers definidos pelo MetalThursday utilizam português.
 *
 * @return array<string, mixed> Configurações de correio eletrónico.
 *
 * @since 1.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Mailer predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'MAIL_MAILER',
        'registo',
    ),

    /*
    |--------------------------------------------------------------------------
    | Mailers disponíveis
    |--------------------------------------------------------------------------
    */

    'mailers' => [
        /*
         * Envia mensagens através de um servidor SMTP.
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

            'timeout' => (int) env(
                'MAIL_TIMEOUT',
                30,
            ),

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
         * Escreve as mensagens no sistema de registos sem as enviar.
         */
        'registo' => [
            'transport' => 'log',

            'channel' => env(
                'MAIL_LOG_CHANNEL',
                'diario',
            ),
        ],

        /*
         * Mantém as mensagens em memória durante o processo atual.
         *
         * Este mailer é utilizado exclusivamente pelos testes automatizados.
         */
        'memoria' => [
            'transport' => 'array',
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
