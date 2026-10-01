<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\DirecaoOrdenacao;
use App\Enumeracoes\OrdenacaoMetalThursday;
use App\Filtros\FiltrosMetalThursday;
use Illuminate\Http\Request;

/**
 * Prepara os dados dos controlos da listagem de MetalThursdays.
 *
 * @since 2.0.0
 */
final class ServicoControlosListagemMetalThursday
{
    /** Identificador da vista completa. */
    private const VISTA_COMPLETA = 'completa';

    /** Identificador da vista simplificada. */
    private const VISTA_SIMPLIFICADA = 'simplificada';

    /** @var list<int> Opções permitidas para o número de registos por página. */
    private const OPCOES_POR_PAGINA = [
        5,
        10,
        20,
        50,
    ];

    /**
     * Cria o serviço com a configuração dos filtros dinâmicos.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoConfiguracaoFiltrosMetalThursday $servicoConfiguracaoFiltros,
    ) {}

    /**
     * Obtém os dados apresentados pelos controlos da listagem.
     *
     * @return array{
     *     gruposFiltrosDisponiveis: array<int, array{
     *         rotulo: string,
     *         filtros: array<int, array{
     *             chave: string,
     *             rotulo: string,
     *             parametro: string,
     *             tipo: 'selecao'|'data'|'sim_nao',
     *             chaveDados: string|null
     *         }>
     *     }>,
     *     nomeParametroPesquisa: string,
     *     pesquisaAtual: string,
     *     opcoesPorPagina: list<int>,
     *     porPagina: int,
     *     nomeParametroVista: string,
     *     vistaAtual: string,
     *     vistaCompleta: string,
     *     vistaSimplificada: string,
     *     nomeParametroPorPagina: string,
     *     nomeParametroOrdenacao: string,
     *     ordenacaoAtual: string,
     *     opcoesOrdenacao: list<array{chave: string, valor: string}>,
     *     nomeParametroDirecaoOrdenacao: string,
     *     direcaoOrdenacaoAtual: string,
     *     opcoesDirecaoOrdenacao: list<array{chave: string, valor: string}>,
     *     textoBotaoAlternarVista: string,
     *     ligacaoLimparFiltros: string
     * } Dados preparados.
     *
     * @since 2.0.0
     */
    public function obterDados(
        Request $pedido,
        string $tipoVista,
        int $porPagina,
    ): array {
        $pesquisaRecebida = $pedido->query(
            FiltrosMetalThursday::PARAMETRO_PESQUISA,
        );

        $pesquisaAtual = is_string(
            $pesquisaRecebida,
        )
            ? $pesquisaRecebida
            : '';

        $ordenacao =
            OrdenacaoMetalThursday::tentarCriar(
                $pedido->query(
                    'ordenar_por',
                ),
            )
            ?? OrdenacaoMetalThursday::Data;

        $direcaoOrdenacao =
            DirecaoOrdenacao::tentarCriar(
                $pedido->query(
                    'direcao_ordenacao',
                ),
            )
            ?? DirecaoOrdenacao::Descendente;

        $vistaAtual =
            $tipoVista === self::VISTA_SIMPLIFICADA
            ? self::VISTA_SIMPLIFICADA
            : self::VISTA_COMPLETA;

        return [
            'gruposFiltrosDisponiveis' => $this
                ->servicoConfiguracaoFiltros
                ->obterGruposDisponiveis(),

            'nomeParametroPesquisa' => FiltrosMetalThursday::PARAMETRO_PESQUISA,

            'pesquisaAtual' => $pesquisaAtual,

            'opcoesPorPagina' => self::OPCOES_POR_PAGINA,

            'porPagina' => $porPagina,

            'nomeParametroVista' => 'vista',

            'vistaAtual' => $vistaAtual,

            'vistaCompleta' => self::VISTA_COMPLETA,

            'vistaSimplificada' => self::VISTA_SIMPLIFICADA,

            'nomeParametroPorPagina' => 'por_pagina',

            'nomeParametroOrdenacao' => 'ordenar_por',

            'ordenacaoAtual' => $ordenacao->value,

            'opcoesOrdenacao' => [
                [
                    'chave' => OrdenacaoMetalThursday::Data->value,

                    'valor' => 'Data',
                ],
                [
                    'chave' => OrdenacaoMetalThursday::Classificacao->value,

                    'valor' => 'Avaliação média',
                ],
                [
                    'chave' => OrdenacaoMetalThursday::MinhaClassificacao->value,

                    'valor' => 'A minha avaliação',
                ],
            ],

            'nomeParametroDirecaoOrdenacao' => 'direcao_ordenacao',

            'direcaoOrdenacaoAtual' => $direcaoOrdenacao->value,

            'opcoesDirecaoOrdenacao' => [
                [
                    'chave' => DirecaoOrdenacao::Ascendente->value,

                    'valor' => 'Ascendente',
                ],
                [
                    'chave' => DirecaoOrdenacao::Descendente->value,

                    'valor' => 'Descendente',
                ],
            ],

            'textoBotaoAlternarVista' => $vistaAtual === self::VISTA_SIMPLIFICADA
                ? 'Ver vista completa'
                : 'Ver vista simplificada',

            'ligacaoLimparFiltros' => route(
                'inicio',
                [
                    'vista' => $vistaAtual,
                ],
            ),
        ];
    }
}
