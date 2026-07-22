<?php

/**
 * Retorna os filtros da aplicação.
 *
 * @return array
 *
 * @since 1.0
 *
 * @version 1.0
 */
return [
    /**
     * Define os parâmetros da URL.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    'params' => [
        'view' => [
            'param' => 'vista',
            'values' => [
                'full' => 'completa',
                'simplified' => 'simplificada',
            ],
        ],
        'per_page' => [
            'param' => 'por_pagina',
        ],
        'sort_by' => [
            'param' => 'ordenar_por',
            'options' => [
                ['key' => 'rating',    'value' => 'avaliacao',       'label' => 'Avaliação Média'],
                ['key' => 'date',      'value' => 'data',            'label' => 'Data'],
                ['key' => 'my_rating', 'value' => 'minha_avaliacao', 'label' => 'Minha Avaliação'],
            ],
        ],
        'sort_direction' => [
            'param' => 'ordem',
            'options' => [
                ['key' => 'asc',  'value' => 'ascendente',  'label' => 'Ascendente'],
                ['key' => 'desc', 'value' => 'descendente', 'label' => 'Descendente'],
            ],
        ],
    ],
    /**
     * Define os filtros de MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    'metalthursday' => [
        'Filtros Gerais' => [
            [
                'key' => 'author',
                'label' => 'Autor',
                'type' => 'select',
                'param' => 'autor',
            ],
            [
                'key' => 'band',
                'label' => 'Banda',
                'type' => 'select',
                'param' => 'banda',
            ],
            [
                'key' => 'date_to',
                'label' => 'Data Até',
                'type' => 'date',
                'param' => 'ate',
            ],
            [
                'key' => 'date_from',
                'label' => 'Data Desde',
                'type' => 'date',
                'param' => 'desde',
            ],
            [
                'key' => 'date',
                'label' => 'Data Específica',
                'type' => 'date',
                'param' => 'data',
            ],
            [
                'key' => 'edition',
                'label' => 'Edição',
                'type' => 'select',
                'param' => 'edicao',
            ],
            [
                'key' => 'genre',
                'label' => 'Género',
                'type' => 'select',
                'param' => 'genero',
            ],
        ],
        'Meus Filtros' => [
            [
                'key' => 'authored_by_me',
                'label' => 'Da Minha Autoria',
                'type' => 'yes_no',
                'param' => 'minha_autoria',
            ],
            [
                'key' => 'nominated',
                'label' => 'Em que fui Nomeado',
                'type' => 'yes_no',
                'param' => 'fui_nomeado',
            ],
            [
                'key' => 'rated',
                'label' => 'Que eu Avaliei',
                'type' => 'yes_no',
                'param' => 'avaliei',
            ],
            [
                'key' => 'listened',
                'label' => 'Que eu Ouvi',
                'type' => 'yes_no',
                'param' => 'ouvi',
            ],
        ],
    ],
];
