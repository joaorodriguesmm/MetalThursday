<?php

declare(strict_types=1);

/**
 * Configura os limites HTTP das operações de interação.
 *
 * O limite por utilizador é partilhado entre comentários, gostos,
 * avaliações e audições para prevenir rajadas de pedidos de escrita.
 */
return [
    'limites_pedidos_http' => [
        'escritas_por_minuto' => (int) env(
            'INTERACOES_ESCRITAS_POR_MINUTO',
            30,
        ),
    ],
];
