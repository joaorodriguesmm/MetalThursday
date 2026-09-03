<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Endereço base
    |--------------------------------------------------------------------------
    |
    | Endereço da API pública do MusicBrainz.
    |
    */
    'base_url' => env(
        'MUSICBRAINZ_BASE_URL',
        'https://musicbrainz.org',
    ),

    /*
    |--------------------------------------------------------------------------
    | User-Agent
    |--------------------------------------------------------------------------
    |
    | O MusicBrainz exige que os clientes se identifiquem através de um
    | User-Agent próprio.
    |
    */
    'user_agent' => env(
        'MUSICBRAINZ_USER_AGENT',
        'MetalThursday/2.0 (https://github.com/joaorodriguesmm/MetalThursday)',
    ),

    /*
    |--------------------------------------------------------------------------
    | Limites de comunicação
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env(
        'MUSICBRAINZ_TIMEOUT',
        10,
    ),

    'tentativas' => (int) env(
        'MUSICBRAINZ_TENTATIVAS',
        3,
    ),

    'intervalo_repeticao_ms' => (int) env(
        'MUSICBRAINZ_INTERVALO_REPETICAO_MS',
        1000,
    ),

    /*
    |--------------------------------------------------------------------------
    | Intervalo mínimo entre pedidos
    |--------------------------------------------------------------------------
    |
    | O limite é partilhado pela aplicação inteira e aplica-se também a
    | pedidos provenientes de processos ou utilizadores diferentes.
    |
    */
    'intervalo_minimo_pedidos_ms' => (int) env(
        'MUSICBRAINZ_INTERVALO_MINIMO_PEDIDOS_MS',
        1000,
    ),
];
