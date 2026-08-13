<?php

declare(strict_types=1);

/**
 * Define os filtros dinâmicos da aplicação.
 *
 * Cada filtro declara a chave interna utilizada pela interface, o nome do
 * parâmetro público, o tipo de campo e, quando necessário, a coleção de dados
 * usada para construir as opções de seleção.
 *
 * @return array{
 *     metal_thursday: array<int, array{
 *         rotulo: string,
 *         filtros: array<int, array{
 *             chave: string,
 *             rotulo: string,
 *             parametro: string,
 *             tipo: 'selecao'|'data'|'sim_nao',
 *             chaveDados: string|null
 *         }>
 *     }>
 * } Configuração dos filtros dinâmicos.
 *
 * @since 1.0.0
 */
return [
    'metal_thursday' => [
        [
            'rotulo' => 'Filtros gerais',

            'filtros' => [
                [
                    'chave' => 'autor',

                    'rotulo' => 'Autor',

                    'parametro' => 'autor',

                    'tipo' => 'selecao',

                    'chaveDados' => 'utilizadores',
                ],
                [
                    'chave' => 'banda',

                    'rotulo' => 'Banda',

                    'parametro' => 'banda',

                    'tipo' => 'selecao',

                    'chaveDados' => 'bandas',
                ],
                [
                    'chave' => 'data_ate',

                    'rotulo' => 'Data até',

                    'parametro' => 'data_ate',

                    'tipo' => 'data',

                    'chaveDados' => null,
                ],
                [
                    'chave' => 'data_desde',

                    'rotulo' => 'Data desde',

                    'parametro' => 'data_desde',

                    'tipo' => 'data',

                    'chaveDados' => null,
                ],
                [
                    'chave' => 'data',

                    'rotulo' => 'Data específica',

                    'parametro' => 'data',

                    'tipo' => 'data',

                    'chaveDados' => null,
                ],
                [
                    'chave' => 'edicao',

                    'rotulo' => 'Edição',

                    'parametro' => 'edicao',

                    'tipo' => 'selecao',

                    'chaveDados' => 'edicoes',
                ],
                [
                    'chave' => 'genero',

                    'rotulo' => 'Género',

                    'parametro' => 'genero',

                    'tipo' => 'selecao',

                    'chaveDados' => 'generos',
                ],
            ],
        ],
        [
            'rotulo' => 'Os meus filtros',

            'filtros' => [
                [
                    'chave' => 'autoria_utilizador',

                    'rotulo' => 'Da minha autoria',

                    'parametro' => 'autoria_utilizador',

                    'tipo' => 'sim_nao',

                    'chaveDados' => null,
                ],
                [
                    'chave' => 'nomeacao',

                    'rotulo' => 'Em que fui nomeado',

                    'parametro' => 'nomeacao',

                    'tipo' => 'sim_nao',

                    'chaveDados' => null,
                ],
                [
                    'chave' => 'avaliacao',

                    'rotulo' => 'Que avaliei',

                    'parametro' => 'avaliacao',

                    'tipo' => 'sim_nao',

                    'chaveDados' => null,
                ],
                [
                    'chave' => 'audicao',

                    'rotulo' => 'Que ouvi',

                    'parametro' => 'audicao',

                    'tipo' => 'sim_nao',

                    'chaveDados' => null,
                ],
            ],
        ],
    ],
];
