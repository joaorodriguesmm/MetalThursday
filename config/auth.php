<?php

declare(strict_types=1);

use App\Models\Autenticacao\Utilizador;

/**
 * Define as configurações de autenticação da aplicação.
 *
 * Os nomes das chaves e os identificadores `web` e `users` permanecem em
 * inglês por corresponderem às convenções utilizadas pelo Laravel e pelo
 * respetivo ecossistema de autenticação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Configurações predefinidas
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env(
            'AUTH_GUARD',
            'web',
        ),

        'passwords' => env(
            'AUTH_PASSWORD_BROKER',
            'users',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards de autenticação
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'web' => [
            'driver' => 'session',

            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers de utilizadores
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',

            'model' => env(
                'AUTH_MODEL',
                Utilizador::class,
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redefinição de palavras-passe
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',

            'table' => env(
                'AUTH_PASSWORD_RESET_TOKEN_TABLE',
                'password_reset_tokens',
            ),

            /*
             * Número de minutos durante os quais o token é válido.
             */
            'expire' => (int) env(
                'AUTH_PASSWORD_RESET_TOKEN_EXPIRATION',
                60,
            ),

            /*
             * Número de segundos que devem decorrer antes de poder ser
             * solicitado um novo token.
             */
            'throttle' => (int) env(
                'AUTH_PASSWORD_RESET_TOKEN_THROTTLE',
                60,
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Confirmação da palavra-passe
    |--------------------------------------------------------------------------
    */

    'password_timeout' => (int) env(
        'AUTH_PASSWORD_TIMEOUT',
        10800,
    ),
];
