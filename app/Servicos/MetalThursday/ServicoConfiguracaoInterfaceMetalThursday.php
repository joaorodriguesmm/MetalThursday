<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

/**
 * Prepara a configuração de frontend utilizada pelo MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoConfiguracaoInterfaceMetalThursday
{
    /**
     * Cria o serviço.
     *
     * @param  ServicoOpcoesMetalThursday  $servicoOpcoes  Serviço responsável
     *                                                     pelas opções de leitura.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoOpcoesMetalThursday $servicoOpcoes,
    ) {}

    /**
     * Prepara a configuração utilizada pelo JavaScript dos formulários.
     *
     * @return array{
     *     enderecos: array{
     *         guardarEdicao: string,
     *         guardarArtista: string,
     *         guardarGenero: string,
     *         pesquisarLancamentos: string,
     *         importarLancamento: string,
     *         obterUtilizadorHaMaisTempoSemNomeacao: string
     *     }
     * } Configuração dos formulários.
     *
     * @since 2.0.0
     */
    public function obterConfiguracaoFormulario(): array
    {
        return [
            'enderecos' => [
                'guardarEdicao' => route(
                    'edicoes.guardar',
                ),

                'guardarArtista' => route(
                    'artistas.guardar',
                ),

                'guardarGenero' => route(
                    'generos.guardar',
                ),

                'pesquisarLancamentos' => route(
                    'lancamentos.importacao.pesquisar',
                ),

                'importarLancamento' => route(
                    'lancamentos.importacao.importar',
                    [
                        'identificadorDiscogs' => '__IDENTIFICADOR_DISCOGS__',
                    ],
                ),

                'obterUtilizadorHaMaisTempoSemNomeacao' => route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                ),
            ],
        ];
    }

    /**
     * Prepara a configuração utilizada pelo JavaScript da listagem.
     *
     * @param  array<int, array{
     *     rotulo: string,
     *     filtros: array<int, array{
     *         chave: string,
     *         rotulo: string,
     *         parametro: string,
     *         tipo: 'selecao'|'data'|'sim_nao',
     *         chaveDados: string|null
     *     }>
     * }>  $gruposFiltrosDisponiveis  Grupos de filtros disponíveis.
     * @return array{
     *     dadosFiltros: array{
     *         edicoes: array<int, array{
     *             identificador: int,
     *             nome: string
     *         }>,
     *         utilizadores: array<int, array{
     *             identificador: int,
     *             nome: string
     *         }>,
     *         artistas: array<int, array{
     *             identificador: int,
     *             nome: string
     *         }>,
     *         generos: array<int, array{
     *             identificador: int,
     *             nome: string
     *         }>
     *     },
     *     filtrosDisponiveis: array<string, array{
     *         chave: string,
     *         rotulo: string,
     *         parametro: string,
     *         tipo: 'selecao'|'data'|'sim_nao',
     *         chaveDados: string|null
     *     }>,
     *     vistas: array{
     *         completa: string,
     *         simplificada: string
     *     }
     * } Configuração preparada.
     *
     * @since 2.0.0
     */
    public function obterConfiguracaoListagemMetalThursday(
        array $gruposFiltrosDisponiveis,
    ): array {
        $filtrosPorChave = [];

        foreach ($gruposFiltrosDisponiveis as $grupo) {
            foreach ($grupo['filtros'] as $filtro) {
                $filtrosPorChave[$filtro['chave']] =
                    $filtro;
            }
        }

        return [
            'dadosFiltros' => [
                'edicoes' => $this
                    ->servicoOpcoes
                    ->serializarOpcoesSelecao(
                        $this
                            ->servicoOpcoes
                            ->obterEdicoesParaSelecao(),
                    ),

                'utilizadores' => $this
                    ->servicoOpcoes
                    ->serializarOpcoesSelecao(
                        $this
                            ->servicoOpcoes
                            ->obterUtilizadoresParaSelecao(),
                    ),

                'artistas' => $this
                    ->servicoOpcoes
                    ->serializarOpcoesSelecao(
                        $this
                            ->servicoOpcoes
                            ->obterArtistasParaSelecao(),
                    ),

                'generos' => $this
                    ->servicoOpcoes
                    ->serializarOpcoesSelecao(
                        $this
                            ->servicoOpcoes
                            ->obterGenerosParaSelecao(),
                    ),
            ],

            'filtrosDisponiveis' => $filtrosPorChave,

            'vistas' => [
                'completa' => ServicoParametrosListagemMetalThursday::VISTA_COMPLETA,

                'simplificada' => ServicoParametrosListagemMetalThursday::VISTA_SIMPLIFICADA,
            ],
        ];
    }
}
