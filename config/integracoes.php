<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Limites HTTP das integrações
    |--------------------------------------------------------------------------
    |
    | Estes limites são aplicados por utilizador autenticado aos endpoints
    | internos que desencadeiam consultas a serviços externos. Não substituem
    | os limites globais de cada fornecedor.
    |
    */
    'limites_pedidos_http' => [
        'discogs_por_minuto' => (int) env(
            'INTEGRACOES_DISCOGS_PEDIDOS_POR_MINUTO',
            20,
        ),

        'artistas_por_minuto' => (int) env(
            'INTEGRACOES_ARTISTAS_PEDIDOS_POR_MINUTO',
            12,
        ),
    ],
];
