<?php

declare(strict_types=1);

/**
 * Define as configurações gerais da aplicação.
 *
 * Os nomes das chaves permanecem em inglês por corresponderem aos contratos
 * internos de configuração do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Identificação e ambiente
    |--------------------------------------------------------------------------
    */

    'name' => env(
        'APP_NAME',
        'MetalThursday',
    ),

    'env' => env(
        'APP_ENV',
        'production',
    ),

    'debug' => (bool) env(
        'APP_DEBUG',
        false,
    ),

    'url' => env(
        'APP_URL',
        'http://localhost',
    ),

    /*
    |--------------------------------------------------------------------------
    | Localização
    |--------------------------------------------------------------------------
    */

    'timezone' => env(
        'APP_TIMEZONE',
        'Europe/Lisbon',
    ),

    'locale' => env(
        'APP_LOCALE',
        'pt',
    ),

    'fallback_locale' => env(
        'APP_FALLBACK_LOCALE',
        'pt',
    ),

    'faker_locale' => env(
        'APP_FAKER_LOCALE',
        'pt_PT',
    ),

    /*
    |--------------------------------------------------------------------------
    | Encriptação
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env(
        'APP_KEY',
    ),

    'previous_keys' => [
        ...array_values(
            array_filter(
                array_map(
                    static fn (
                        string $chave,
                    ): string => trim(
                        $chave,
                    ),
                    explode(
                        ',',
                        (string) env(
                            'APP_PREVIOUS_KEYS',
                            '',
                        ),
                    ),
                ),
                static fn (
                    string $chave,
                ): bool => $chave !== '',
            ),
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo de manutenção
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env(
            'APP_MAINTENANCE_DRIVER',
            'file',
        ),

        'store' => env(
            'APP_MAINTENANCE_STORE',
            'database',
        ),
    ],
];
