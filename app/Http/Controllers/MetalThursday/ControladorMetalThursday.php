<?php

declare(strict_types=1);

namespace App\Http\Controllers\MetalThursday;

use App\Enumeracoes\DirecaoOrdenacao;
use App\Enumeracoes\OrdenacaoMetalThursday;
use App\Filtros\FiltrosMetalThursday;
use App\Http\Controllers\Controller;
use App\Http\Requests\MetalThursday\StoreMetalThursdayRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\Pais;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use App\Notifications\NewMetalThursdayCreated;
use App\Notifications\UserNominated;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

/**
 * Gere as operações HTTP relacionadas com MetalThursdays.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorMetalThursday extends Controller
{
    use AuthorizesRequests;

    /**
     * Opções permitidas para o número de registos por página.
     *
     * @var array<int, int>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const OPCOES_POR_PAGINA = [
        5,
        10,
        20,
        50,
    ];

    /**
     * Número predefinido de registos por página.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const POR_PAGINA_PREDEFINIDO = 10;

    /**
     * Cria o controlador.
     *
     * @param  ServicoPersistenciaMetalThursday  $servicoPersistencia  Serviço de
     *                                                                 persistência.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly ServicoPersistenciaMetalThursday $servicoPersistencia,
    ) {}

    /**
     * Apresenta a listagem de MetalThursdays.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  FiltrosMetalThursday  $filtros  Serviço de filtros.
     * @return View Página inicial.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function index(
        Request $pedido,
        FiltrosMetalThursday $filtros,
    ): View {
        $this->authorize(
            'viewAny',
            MetalThursday::class,
        );

        $this->normalizarParametrosLegados(
            $pedido,
        );

        $porPagina =
            $this->obterNumeroPorPagina(
                $pedido,
            );

        $tipoVista =
            $this->obterTipoVista(
                $pedido,
            );

        $metalThursdays = null;
        $seccoesSimplificadas = null;

        if ($tipoVista === 'simplified') {
            $consulta =
                $this->criarConsultaSimplificada();

            $seccoesSimplificadas =
                $filtros
                    ->aplicar($consulta)
                    ->paginate($porPagina)
                    ->withQueryString();
        } else {
            $consulta =
                $this->criarConsultaCompleta();

            $metalThursdays =
                $filtros
                    ->aplicar($consulta)
                    ->paginate($porPagina)
                    ->withQueryString();
        }

        return view(
            'metalthursday.index',
            [
                /*
                 * As chaves permanecem temporariamente em inglês até à
                 * revisão das vistas.
                 */
                'metalThursdays' => $metalThursdays,

                'simplifiedSections' => $seccoesSimplificadas,

                'editions' => Edicao::query()
                    ->select([
                        'id',
                        'nome',
                        'data_inicio',
                        'data_fim',
                    ])
                    ->orderBy('nome')
                    ->get(),

                'users' => Utilizador::query()
                    ->selecionaveis()
                    ->select([
                        'id',
                        'nome',
                    ])
                    ->get(),

                'bands' => Banda::query()
                    ->select([
                        'id',
                        'nome',
                        'pais_id',
                    ])
                    ->orderBy('nome')
                    ->get(),

                'genres' => Genero::query()
                    ->select([
                        'id',
                        'nome',
                    ])
                    ->orderBy('nome')
                    ->get(),

                'perPageOptions' => self::OPCOES_POR_PAGINA,

                'perPage' => $porPagina,

                'viewType' => $tipoVista,

                'availableFilters' => config(
                    'filters.metalthursday',
                    [],
                ),

                'viewParams' => $this->obterParametrosVista(
                    $pedido,
                ),
            ],
        );
    }

    /**
     * Apresenta o formulário de criação.
     *
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function create(): View
    {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        return view(
            'metalthursday.create',
            $this->obterDadosFormulario(),
        );
    }

    /**
     * Guarda uma nova MetalThursday.
     *
     * @param  StoreMetalThursdayRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para a listagem.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function store(
        StoreMetalThursdayRequest $pedido,
    ): RedirectResponse {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        $metalThursday =
            $this->servicoPersistencia->criar(
                $pedido->validated(),
            );

        $identificadorCriador = $pedido
            ->user()
            ?->getAuthIdentifier();

        $this->notificarCriacao(
            $metalThursday,
            is_numeric($identificadorCriador)
                ? (int) $identificadorCriador
                : null,
        );

        return redirect()
            ->route('home')
            ->with(
                'estado',
                'MetalThursday criada com sucesso.',
            );
    }

    /**
     * Apresenta os detalhes de uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday apresentada.
     * @return View Página de detalhes.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function show(
        MetalThursday $metalThursday,
    ): View {
        $this->authorize(
            'view',
            $metalThursday,
        );

        $metalThursday
            ->loadCount([
                'comentarios',
                'avaliacoes',
                'audicoes',
            ])
            ->loadAvg(
                'avaliacoes',
                'pontuacao',
            )
            ->load([
                'edicao',
                'autor',
                'proximoNomeado',
                'criadoPor',
                'avaliacoes.utilizador',
                'audicoes.utilizador',
                'avaliacaoUtilizadorAutenticado',
                'audicaoUtilizadorAutenticado',

                'seccoes' => static function (
                    Builder $consulta,
                ): void {
                    $consulta
                        ->withCount([
                            'comentarios',
                            'avaliacoes',
                            'audicoes',
                        ])
                        ->withAvg(
                            'avaliacoes',
                            'pontuacao',
                        )
                        ->with([
                            'tipoSeccao',
                            'banda.pais',
                            'banda.generos',
                            'avaliacoes.utilizador',
                            'audicoes.utilizador',
                            'avaliacaoUtilizadorAutenticado',
                            'audicaoUtilizadorAutenticado',
                        ]);
                },
            ]);

        return view(
            'metalthursday.show',
            [
                'mt' => $metalThursday,
            ],
        );
    }

    /**
     * Apresenta o formulário de edição.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edit(
        MetalThursday $metalThursday,
    ): View {
        $this->authorize(
            'update',
            $metalThursday,
        );

        $metalThursday->load([
            'seccoes.tipoSeccao',
            'seccoes.banda',
        ]);

        return view(
            'metalthursday.edit',
            [
                'metalThursday' => $metalThursday,

                ...$this->obterDadosFormulario(),
            ],
        );
    }

    /**
     * Atualiza uma MetalThursday.
     *
     * @param  StoreMetalThursdayRequest  $pedido  Pedido validado.
     * @param  MetalThursday  $metalThursday  MetalThursday atualizada.
     * @return RedirectResponse Redirecionamento para a listagem.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function update(
        StoreMetalThursdayRequest $pedido,
        MetalThursday $metalThursday,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $metalThursday,
        );

        $this->servicoPersistencia->atualizar(
            $metalThursday,
            $pedido->validated(),
        );

        return redirect()
            ->route('home')
            ->with(
                'estado',
                'MetalThursday atualizada com sucesso.',
            );
    }

    /**
     * Elimina uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday eliminada.
     * @return JsonResponse Resultado da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function destroy(
        MetalThursday $metalThursday,
    ): JsonResponse {
        $this->authorize(
            'delete',
            $metalThursday,
        );

        $metalThursday->deleteOrFail();

        return response()->json([
            'success' => true,
            'message' => 'MetalThursday eliminada com sucesso.',
        ]);
    }

    /**
     * Obtém o utilizador há mais tempo sem ser nomeado.
     *
     * Os utilizadores nunca nomeados são apresentados primeiro. Em caso de
     * empate, é utilizado o nome e depois o identificador.
     *
     * @return JsonResponse Identificador do utilizador encontrado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function obterUtilizadorHaMaisTempoSemNomeacao(): JsonResponse
    {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        $ultimasNomeacoes = MetalThursday::query()
            ->selectRaw(
                'proximo_nomeado_id, MAX(data) AS ultima_nomeacao_em',
            )
            ->whereNotNull(
                'proximo_nomeado_id',
            )
            ->groupBy(
                'proximo_nomeado_id',
            );

        $utilizador = Utilizador::query()
            ->selecionaveis()
            ->leftJoinSub(
                $ultimasNomeacoes,
                'ultimas_nomeacoes',
                static function (
                    JoinClause $juncao,
                ): void {
                    $juncao->on(
                        'utilizadores.id',
                        '=',
                        'ultimas_nomeacoes.proximo_nomeado_id',
                    );
                },
            )
            ->reorder()
            ->orderByRaw(
                'CASE '
                    .'WHEN ultimas_nomeacoes.ultima_nomeacao_em IS NULL '
                    .'THEN 0 ELSE 1 END ASC',
            )
            ->orderBy(
                'ultimas_nomeacoes.ultima_nomeacao_em',
            )
            ->orderBy(
                'utilizadores.nome',
            )
            ->orderBy(
                'utilizadores.id',
            )
            ->select([
                'utilizadores.id',
            ])
            ->first();

        return response()->json([
            'id' => $utilizador?->getKey(),
        ]);
    }

    /**
     * Cria a consulta da vista completa.
     *
     * @return Builder<MetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarConsultaCompleta(): Builder
    {
        return MetalThursday::query()
            ->withCount([
                'comentarios',
                'avaliacoes',
                'audicoes',
            ])
            ->withAvg(
                'avaliacoes',
                'pontuacao',
            )
            ->with([
                'edicao',
                'autor',
                'proximoNomeado',
                'avaliacoes.utilizador',
                'audicoes.utilizador',
                'avaliacaoUtilizadorAutenticado',
                'audicaoUtilizadorAutenticado',

                'seccoes' => static function (
                    Builder $consulta,
                ): void {
                    $consulta
                        ->withCount([
                            'comentarios',
                            'avaliacoes',
                            'audicoes',
                        ])
                        ->withAvg(
                            'avaliacoes',
                            'pontuacao',
                        )
                        ->with([
                            'tipoSeccao',
                            'banda.pais',
                            'banda.generos',
                            'avaliacoes.utilizador',
                            'audicoes.utilizador',
                            'avaliacaoUtilizadorAutenticado',
                            'audicaoUtilizadorAutenticado',
                        ]);
                },
            ]);
    }

    /**
     * Cria a consulta da vista simplificada.
     *
     * @return Builder<SeccaoMetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarConsultaSimplificada(): Builder
    {
        return SeccaoMetalThursday::query()
            ->withCount([
                'avaliacoes',
                'audicoes',
            ])
            ->withAvg(
                'avaliacoes',
                'pontuacao',
            )
            ->with([
                'metalThursday.autor',
                'banda.pais',
                'banda.generos',
                'tipoSeccao',
                'avaliacoes.utilizador',
                'audicoes.utilizador',
                'avaliacaoUtilizadorAutenticado',
                'audicaoUtilizadorAutenticado',
            ])
            ->whereHas(
                'tipoSeccao',
                static fn (
                    Builder $consulta,
                ): Builder => $consulta->where(
                    'tem_detalhes',
                    true,
                ),
            );
    }

    /**
     * Obtém os dados comuns aos formulários.
     *
     * As chaves permanecem temporariamente em inglês até à revisão das vistas.
     *
     * @return array<string, mixed> Dados dos formulários.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterDadosFormulario(): array
    {
        return [
            'editions' => Edicao::query()
                ->select([
                    'id',
                    'nome',
                    'data_inicio',
                    'data_fim',
                ])
                ->orderByDesc(
                    'data_inicio',
                )
                ->orderByDesc('id')
                ->get(),

            'users' => Utilizador::query()
                ->selecionaveis()
                ->select([
                    'id',
                    'nome',
                ])
                ->get(),

            'sectionTypes' => TipoSeccao::query()
                ->select([
                    'id',
                    'nome',
                    'descricao',
                    'tem_detalhes',
                ])
                ->orderBy('nome')
                ->get(),

            'bands' => Banda::query()
                ->select([
                    'id',
                    'nome',
                    'pais_id',
                ])
                ->orderBy('nome')
                ->get(),

            'countries' => Pais::query()
                ->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ])
                ->orderBy('nome')
                ->get(),

            'genres' => Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy('nome')
                ->get(),
        ];
    }

    /**
     * Obtém o número de elementos por página.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return int Número permitido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterNumeroPorPagina(
        Request $pedido,
    ): int {
        $valor = $pedido->query(
            'por_pagina',
            $pedido->query(
                'per_page',
                self::POR_PAGINA_PREDEFINIDO,
            ),
        );

        $numero = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
        );

        if (
            $numero === false
            || ! in_array(
                $numero,
                self::OPCOES_POR_PAGINA,
                true,
            )
        ) {
            return self::POR_PAGINA_PREDEFINIDO;
        }

        return $numero;
    }

    /**
     * Obtém o tipo de vista pedido.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return string Tipo utilizado pelas vistas atuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTipoVista(
        Request $pedido,
    ): string {
        $valor = $pedido->query(
            'vista',
            $pedido->query(
                'view',
                'full',
            ),
        );

        if (! is_string($valor)) {
            return 'full';
        }

        return match (mb_strtolower(trim($valor))) {
            'simplified',
            'simplificada' => 'simplified',

            default => 'full',
        };
    }

    /**
     * Obtém os parâmetros utilizados pela vista atual.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return array<string, mixed> Parâmetros da vista.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterParametrosVista(
        Request $pedido,
    ): array {
        $ordenacao =
            OrdenacaoMetalThursday::tentarCriar(
                $pedido->query(
                    'ordenar_por',
                ),
            )
            ?? OrdenacaoMetalThursday::Data;

        $direcao =
            DirecaoOrdenacao::tentarCriar(
                $pedido->query(
                    'direcao_ordenacao',
                ),
            )
            ?? DirecaoOrdenacao::Descendente;

        return [
            'view' => [
                'name' => 'view',
                'simplified' => 'simplified',
                'full' => 'full',
            ],

            'per_page' => [
                'name' => 'per_page',
            ],

            'sort_by' => [
                'name' => 'ordenar_por',

                'options' => collect([
                    [
                        'key' => 'data',
                        'value' => 'Data',
                    ],
                    [
                        'key' => 'classificacao',
                        'value' => 'Classificação média',
                    ],
                    [
                        'key' => 'minha_classificacao',
                        'value' => 'Minha classificação',
                    ],
                ]),

                'current' => $ordenacao->value,
            ],

            'sort_direction' => [
                'name' => 'direcao_ordenacao',

                'options' => collect([
                    [
                        'key' => 'ascendente',
                        'value' => 'Ascendente',
                    ],
                    [
                        'key' => 'descendente',
                        'value' => 'Descendente',
                    ],
                ]),

                'current' => $direcao->value,
            ],
        ];
    }

    /**
     * Converte parâmetros antigos para os parâmetros atuais.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarParametrosLegados(
        Request $pedido,
    ): void {
        $mapa = [
            'filter_author' => 'filtro_autor',

            'filter_band' => 'filtro_banda',

            'filter_authored_by_me' => 'filtro_autoria_utilizador',

            'filter_date_to' => 'filtro_data_ate',

            'filter_date_from' => 'filtro_data_desde',

            'filter_date' => 'filtro_data',

            'filter_edition' => 'filtro_edicao',

            'filter_nominated' => 'filtro_nomeacao',

            'filter_genre' => 'filtro_genero',

            'filter_rated' => 'filtro_avaliacao',

            'filter_listened' => 'filtro_audicao',

            'sort_by' => 'ordenar_por',

            'sort_direction' => 'direcao_ordenacao',
        ];

        foreach ($mapa as $antigo => $atual) {
            if (
                ! $pedido->query->has($atual)
                && $pedido->query->has($antigo)
            ) {
                $pedido->query->set(
                    $atual,
                    $pedido->query($antigo),
                );
            }

            $pedido->query->remove(
                $antigo,
            );
        }
    }

    /**
     * Envia as notificações relativas à criação.
     *
     * Falhas nas notificações são reportadas sem transformar uma criação já
     * persistida numa resposta de erro.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     * @param  int|null  $identificadorCriador  Criador autenticado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function notificarCriacao(
        MetalThursday $metalThursday,
        ?int $identificadorCriador,
    ): void {
        $metalThursday->loadMissing([
            'autor',
            'proximoNomeado',
        ]);

        $nomeado = $metalThursday
            ->proximoNomeado;

        if ($nomeado !== null) {
            try {
                $nomeado->notify(
                    new UserNominated(
                        $metalThursday,
                    ),
                );
            } catch (Throwable $excecao) {
                report($excecao);
            }
        }

        try {
            $consulta = Utilizador::query()
                ->selecionaveis();

            if ($identificadorCriador !== null) {
                $consulta->where(
                    'utilizadores.id',
                    '!=',
                    $identificadorCriador,
                );
            }

            if ($nomeado !== null) {
                $consulta->where(
                    'utilizadores.id',
                    '!=',
                    $nomeado->getKey(),
                );
            }

            $consulta
                ->reorder('utilizadores.id')
                ->chunkById(
                    100,
                    static function (
                        Collection $destinatarios,
                    ) use (
                        $metalThursday,
                    ): void {
                        Notification::send(
                            $destinatarios,
                            new NewMetalThursdayCreated(
                                $metalThursday,
                            ),
                        );
                    },
                    'utilizadores.id',
                    'id',
                );
        } catch (Throwable $excecao) {
            report($excecao);
        }
    }
}
