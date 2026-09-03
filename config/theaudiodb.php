<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Endereço base
    |--------------------------------------------------------------------------
    */
    'base_url' => env(
        'THEAUDIODB_BASE_URL',
        'https://www.theaudiodb.com',
    ),

    /*
    |--------------------------------------------------------------------------
    | Chave da API
    |--------------------------------------------------------------------------
    |
    | 123 corresponde à chave pública disponibilizada para a API gratuita.
    | Pode ser substituída através do ambiente sem alterar o código.
    |
    */
    'api_key' => env(
        'THEAUDIODB_API_KEY',
        '123',
    ),

    /*
    |--------------------------------------------------------------------------
    | Limites de comunicação
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env(
        'THEAUDIODB_TIMEOUT',
        10,
    ),

    'tentativas' => (int) env(
        'THEAUDIODB_TENTATIVAS',
        2,
    ),

    'intervalo_repeticao_ms' => (int) env(
        'THEAUDIODB_INTERVALO_REPETICAO_MS',
        500,
    ),

    /*
    |--------------------------------------------------------------------------
    | Intervalo mínimo entre pedidos
    |--------------------------------------------------------------------------
    |
    | O intervalo de dois segundos mantém a aplicação dentro do limite de
    | trinta pedidos por minuto previsto para a utilização actual.
    |
    */
    'intervalo_minimo_pedidos_ms' => (int) env(
        'THEAUDIODB_INTERVALO_MINIMO_PEDIDOS_MS',
        2000,
    ),
];
