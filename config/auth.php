<?php

declare(strict_types=1);

use App\Models\Autenticacao\Utilizador;

/**
 * Define as configurações de autenticação da aplicação.
 *
 * Os nomes das chaves, drivers e propriedades permanecem em inglês por
 * corresponderem aos contratos internos de configuração do Laravel. Os
 * identificadores definidos pelo MetalThursday utilizam português.
 *
 * @return array<string, mixed> Configurações de autenticação.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Configurações predefinidas
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => 'sessao',

        'passwords' => 'utilizadores',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards de autenticação
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'sessao' => [
            'driver' => 'session',

            'provider' => 'utilizadores',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers de utilizadores
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'utilizadores' => [
            'driver' => 'eloquent',

            'model' => Utilizador::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redefinição de palavras-passe
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'utilizadores' => [
            'provider' => 'utilizadores',

            'table' => 'tokens_redefinicao_palavra_passe',

            /*
             * Número de minutos durante os quais um token de redefinição
             * permanece válido.
             */
            'expire' => 60,

            /*
             * Número de segundos que devem decorrer antes de poder ser
             * solicitado outro token para o mesmo endereço.
             */
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Confirmação da palavra-passe
    |--------------------------------------------------------------------------
    */

    /*
     * Número de segundos durante os quais uma confirmação recente da
     * palavra-passe permanece válida.
     */
    'password_timeout' => 10800,
];
