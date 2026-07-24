<?php

declare(strict_types=1);

/**
 * Define as configurações dos serviços externos utilizados pela aplicação.
 *
 * As chaves exigidas pelos serviços e pelos transports do Laravel permanecem
 * em inglês. As chaves específicas do MetalThursday utilizam português.
 *
 * @return array<string, mixed>
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Postmark
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env(
            'POSTMARK_API_KEY',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend
    |--------------------------------------------------------------------------
    */

    'resend' => [
        'key' => env(
            'RESEND_API_KEY',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SES
    |--------------------------------------------------------------------------
    */

    'ses' => [
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN',
            ),

            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL',
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | YouTube
    |--------------------------------------------------------------------------
    */

    'youtube' => [
        'chave_api' => env(
            'YOUTUBE_API_KEY',
        ),
    ],
];
