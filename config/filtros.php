<?php

declare(strict_types=1);

/**
 * Define os parâmetros, opções de ordenação e filtros da aplicação.
 *
 * Todas as chaves pertencem à aplicação e, por isso, utilizam nomes em
 * português.
 *
 * @return array{
 *     parametros: array<string, mixed>,
 *     metal_thursday: array{
 *         grupos: array<string, array{
 *             rotulo: string,
 *             filtros: list<array{
 *                 chave: string,
 *                 rotulo: string,
 *                 tipo: string,
 *                 parametro: string
 *             }>
 *         }>
 *     }
 * }
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Parâmetros gerais
    |--------------------------------------------------------------------------
    */

    'parametros' => [
        'vista' => [
            'parametro' => 'vista',

            'valores' => [
                'completa' => 'completa',
                'simplificada' => 'simplificada',
            ],
        ],

        'por_pagina' => [
            'parametro' => 'por_pagina',
        ],

        'ordenacao' => [
            'parametro' => 'ordenar_por',

            'opcoes' => [
                [
                    'chave' => 'avaliacao',
                    'valor' => 'avaliacao',
                    'rotulo' => 'Avaliação média',
                ],
                [
                    'chave' => 'data',
                    'valor' => 'data',
                    'rotulo' => 'Data',
                ],
                [
                    'chave' => 'minha_avaliacao',
                    'valor' => 'minha_avaliacao',
                    'rotulo' => 'A minha avaliação',
                ],
            ],
        ],

        'direcao_ordenacao' => [
            'parametro' => 'ordem',

            'opcoes' => [
                [
                    'chave' => 'ascendente',
                    'valor' => 'ascendente',
                    'rotulo' => 'Ascendente',
                ],
                [
                    'chave' => 'descendente',
                    'valor' => 'descendente',
                    'rotulo' => 'Descendente',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtros de MetalThursday
    |--------------------------------------------------------------------------
    */

    'metal_thursday' => [
        'grupos' => [
            'gerais' => [
                'rotulo' => 'Filtros gerais',

                'filtros' => [
                    [
                        'chave' => 'autor',
                        'rotulo' => 'Autor',
                        'tipo' => 'selecao',
                        'parametro' => 'autor',
                    ],
                    [
                        'chave' => 'banda',
                        'rotulo' => 'Banda',
                        'tipo' => 'selecao',
                        'parametro' => 'banda',
                    ],
                    [
                        'chave' => 'data_ate',
                        'rotulo' => 'Data até',
                        'tipo' => 'data',
                        'parametro' => 'ate',
                    ],
                    [
                        'chave' => 'data_desde',
                        'rotulo' => 'Data desde',
                        'tipo' => 'data',
                        'parametro' => 'desde',
                    ],
                    [
                        'chave' => 'data',
                        'rotulo' => 'Data específica',
                        'tipo' => 'data',
                        'parametro' => 'data',
                    ],
                    [
                        'chave' => 'edicao',
                        'rotulo' => 'Edição',
                        'tipo' => 'selecao',
                        'parametro' => 'edicao',
                    ],
                    [
                        'chave' => 'genero',
                        'rotulo' => 'Género',
                        'tipo' => 'selecao',
                        'parametro' => 'genero',
                    ],
                ],
            ],

            'pessoais' => [
                'rotulo' => 'Os meus filtros',

                'filtros' => [
                    [
                        'chave' => 'minha_autoria',
                        'rotulo' => 'Da minha autoria',
                        'tipo' => 'sim_nao',
                        'parametro' => 'minha_autoria',
                    ],
                    [
                        'chave' => 'fui_nomeado',
                        'rotulo' => 'Em que fui nomeado',
                        'tipo' => 'sim_nao',
                        'parametro' => 'fui_nomeado',
                    ],
                    [
                        'chave' => 'avaliei',
                        'rotulo' => 'Que avaliei',
                        'tipo' => 'sim_nao',
                        'parametro' => 'avaliei',
                    ],
                    [
                        'chave' => 'ouvi',
                        'rotulo' => 'Que ouvi',
                        'tipo' => 'sim_nao',
                        'parametro' => 'ouvi',
                    ],
                ],
            ],
        ],
    ],
];
