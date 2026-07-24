<?php

declare(strict_types=1);

namespace App\Http\Controllers\MetalThursday;

use App\Enumeracoes\DirecaoOrdenacao;
use App\Enumeracoes\OrdenacaoMetalThursday;
use App\Filtros\FiltrosMetalThursday;
use App\Http\Controllers\Controller;
use App\Http\Requests\MetalThursday\GuardarMetalThursdayRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\Pais;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoUtilizadorNomeado;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Gere as operações HTTP relacionadas com MetalThursdays.
 *
 * @since 1.0.0
 *
 * @version 2.2.0
 */
final class ControladorMetalThursday extends Controller
{
    use AuthorizesRequests;

    /**
     * Identificador da vista completa.
     *
     * @var string
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private const VISTA_COMPLETA = 'completa';

    /**
     * Identificador da vista simplificada.
     *
     * @var string
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private const VISTA_SIMPLIFICADA = 'simplificada';

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
     * Número de utilizadores processados por bloco nas notificações.
     *
     * @var int
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    private const UTILIZADORES_POR_BLOCO = 100;

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
     * @return View Página principal.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function index(
        Request $pedido,
        FiltrosMetalThursday $filtros,
    ): View {
        $this->authorize(
            'viewAny',
            MetalThursday::class,
        );

        $porPagina =
            $this->obterNumeroPorPagina(
                $pedido,
            );

        $tipoVista =
            $this->obterTipoVista(
                $pedido,
            );

        $registosMetalThursday = null;
        $seccoesSimplificadas = null;

        if ($tipoVista === self::VISTA_SIMPLIFICADA) {
            $seccoesSimplificadas = $filtros
                ->aplicar(
                    $this->criarConsultaSimplificada(),
                )
                ->paginate(
                    $porPagina,
                )
                ->withQueryString();
        } else {
            $registosMetalThursday = $filtros
                ->aplicar(
                    $this->criarConsultaCompleta(),
                )
                ->paginate(
                    $porPagina,
                )
                ->withQueryString();
        }

        return view(
            'metalthursday.index',
            [
                'registosMetalThursday' => $registosMetalThursday,

                'seccoesSimplificadas' => $seccoesSimplificadas,

                'edicoes' => $this->obterEdicoesParaSelecao(),

                'utilizadores' => $this->obterUtilizadoresParaSelecao(),

                'bandas' => $this->obterBandasParaSelecao(),

                'generos' => $this->obterGenerosParaSelecao(),

                'opcoesPorPagina' => self::OPCOES_POR_PAGINA,

                'porPagina' => $porPagina,

                'tipoVista' => $tipoVista,

                'filtrosDisponiveis' => config(
                    'filters.metalthursday',
                    [],
                ),

                'parametrosVista' => $this->obterParametrosVista(
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
     * @version 2.1.0
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
     * @param  GuardarMetalThursdayRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.2.0
     */
    public function store(
        GuardarMetalThursdayRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        $identificadorCriador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $metalThursday =
            $this->servicoPersistencia
                ->criar(
                    $pedido->validated(),
                );

        $this->notificarCriacao(
            $metalThursday,
            $identificadorCriador,
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' => 'MetalThursday criada com sucesso.',

                    'metal_thursday' => $this->serializarMetalThursday(
                        $metalThursday,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return redirect()
            ->route(
                'home',
            )
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
     * @version 2.1.0
     */
    public function show(
        MetalThursday $metalThursday,
    ): View {
        $this->authorize(
            'view',
            $metalThursday,
        );

        $this->carregarDetalhes(
            $metalThursday,
        );

        return view(
            'metalthursday.show',
            [
                'metalThursday' => $metalThursday,
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
     * @version 2.1.0
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
     * @param  GuardarMetalThursdayRequest  $pedido  Pedido validado.
     * @param  MetalThursday  $metalThursday  MetalThursday atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.2.0
     */
    public function update(
        GuardarMetalThursdayRequest $pedido,
        MetalThursday $metalThursday,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $metalThursday,
        );

        $this->servicoPersistencia
            ->atualizar(
                $metalThursday,
                $pedido->validated(),
            );

        $metalThursday->refresh();

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'MetalThursday atualizada com sucesso.',

                'metal_thursday' => $this->serializarMetalThursday(
                    $metalThursday,
                ),
            ]);
        }

        return redirect()
            ->route(
                'home',
            )
            ->with(
                'estado',
                'MetalThursday atualizada com sucesso.',
            );
    }

    /**
     * Elimina uma MetalThursday.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  MetalThursday  $metalThursday  MetalThursday eliminada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function destroy(
        Request $pedido,
        MetalThursday $metalThursday,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $metalThursday,
        );

        $metalThursday->deleteOrFail();

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return redirect()
            ->route(
                'home',
            )
            ->with(
                'estado',
                'MetalThursday eliminada com sucesso.',
            );
    }

    /**
     * Obtém o utilizador há mais tempo sem ser nomeado.
     *
     * Utilizadores nunca nomeados aparecem primeiro. Em caso de empate, é
     * utilizado o nome e depois o identificador.
     *
     * @return JsonResponse Identificador do utilizador encontrado.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
            'identificador' => is_numeric(
                $utilizador?->getKey(),
            )
                ? (int) $utilizador->getKey()
                : null,
        ]);
    }

    /**
     * Cria a consulta da vista completa.
     *
     * @return Builder<MetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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
     * @version 1.1.0
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
     * Carrega as relações necessárias para a página de detalhes.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday carregada.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function carregarDetalhes(
        MetalThursday $metalThursday,
    ): void {
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
    }

    /**
     * Obtém os dados comuns aos formulários.
     *
     * @return array<string, mixed> Dados dos formulários.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterDadosFormulario(): array
    {
        return [
            'edicoes' => $this->obterEdicoesParaSelecao(),

            'utilizadores' => $this->obterUtilizadoresParaSelecao(),

            'tiposSeccao' => TipoSeccao::query()
                ->select([
                    'id',
                    'nome',
                    'descricao',
                    'tem_detalhes',
                ])
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'bandas' => $this->obterBandasParaSelecao(),

            'paises' => Pais::query()
                ->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ])
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'generos' => $this->obterGenerosParaSelecao(),
        ];
    }

    /**
     * Obtém as edições disponíveis para seleção.
     *
     * @return Collection<int, Edicao> Edições.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterEdicoesParaSelecao(): Collection
    {
        return Edicao::query()
            ->select([
                'id',
                'nome',
                'data_inicio',
                'data_fim',
            ])
            ->orderByDesc(
                'data_inicio',
            )
            ->orderByDesc(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os utilizadores disponíveis para seleção.
     *
     * @return Collection<int, Utilizador> Utilizadores.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadoresParaSelecao(): Collection
    {
        return Utilizador::query()
            ->selecionaveis()
            ->select([
                'id',
                'nome',
            ])
            ->reorder(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém as bandas disponíveis para seleção.
     *
     * @return Collection<int, Banda> Bandas.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterBandasParaSelecao(): Collection
    {
        return Banda::query()
            ->select([
                'id',
                'nome',
                'pais_id',
            ])
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os géneros disponíveis para seleção.
     *
     * @return Collection<int, Genero> Géneros.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterGenerosParaSelecao(): Collection
    {
        return Genero::query()
            ->select([
                'id',
                'nome',
            ])
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém o número de elementos por página.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return int Número permitido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterNumeroPorPagina(
        Request $pedido,
    ): int {
        $numero = filter_var(
            $pedido->query(
                'por_pagina',
                self::POR_PAGINA_PREDEFINIDO,
            ),
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
     * Obtém o tipo da vista pedida.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return string Tipo da vista.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterTipoVista(
        Request $pedido,
    ): string {
        $valor = $pedido->query(
            'vista',
            self::VISTA_COMPLETA,
        );

        if (! is_string($valor)) {
            return self::VISTA_COMPLETA;
        }

        return match (mb_strtolower(
            trim($valor),
        )) {
            self::VISTA_SIMPLIFICADA => self::VISTA_SIMPLIFICADA,

            default => self::VISTA_COMPLETA,
        };
    }

    /**
     * Obtém os parâmetros utilizados pela vista.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return array<string, mixed> Parâmetros da vista.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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
            'vista' => [
                'nome' => 'vista',

                'simplificada' => self::VISTA_SIMPLIFICADA,

                'completa' => self::VISTA_COMPLETA,
            ],

            'por_pagina' => [
                'nome' => 'por_pagina',
            ],

            'ordenacao' => [
                'nome' => 'ordenar_por',

                'opcoes' => [
                    [
                        'chave' => 'data',

                        'valor' => 'Data',
                    ],
                    [
                        'chave' => 'classificacao',

                        'valor' => 'Classificação média',
                    ],
                    [
                        'chave' => 'minha_classificacao',

                        'valor' => 'A minha classificação',
                    ],
                ],

                'atual' => $ordenacao->value,
            ],

            'direcao_ordenacao' => [
                'nome' => 'direcao_ordenacao',

                'opcoes' => [
                    [
                        'chave' => 'ascendente',

                        'valor' => 'Ascendente',
                    ],
                    [
                        'chave' => 'descendente',

                        'valor' => 'Descendente',
                    ],
                ],

                'atual' => $direcao->value,
            ],
        ];
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return int Identificador do utilizador.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.1.0
     *
     * @version 1.1.0
     */
    private function obterIdentificadorUtilizador(
        Request $pedido,
    ): int {
        $utilizador =
            $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para criar uma MetalThursday.',
            );
        }

        $identificador =
            $utilizador->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Converte uma MetalThursday para o formato de resposta HTTP.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday convertida.
     * @return array{
     *     id: int,
     *     nome: string|null,
     *     data: string|null
     * } Dados da MetalThursday.
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    private function serializarMetalThursday(
        MetalThursday $metalThursday,
    ): array {
        $data =
            $metalThursday->data;

        return [
            'id' => (int) $metalThursday->getKey(),

            'nome' => is_string($metalThursday->nome)
                ? $metalThursday->nome
                : null,

            'data' => $data instanceof CarbonInterface
                ? $data->format('Y-m-d')
                : null,
        ];
    }

    /**
     * Envia as notificações relativas à criação.
     *
     * Uma falha no envio não transforma uma criação já persistida numa
     * resposta de erro.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     * @param  int  $identificadorCriador  Criador autenticado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function notificarCriacao(
        MetalThursday $metalThursday,
        int $identificadorCriador,
    ): void {
        $metalThursday->loadMissing([
            'autor',
            'proximoNomeado',
        ]);

        $nomeado =
            $metalThursday->proximoNomeado;

        if ($nomeado instanceof Utilizador) {
            try {
                $nomeado->notify(
                    new NotificacaoUtilizadorNomeado(
                        $metalThursday,
                    ),
                );
            } catch (Throwable $excecao) {
                report(
                    $excecao,
                );
            }
        }

        try {
            $consulta = Utilizador::query()
                ->selecionaveis()
                ->where(
                    'utilizadores.id',
                    '!=',
                    $identificadorCriador,
                );

            if ($nomeado instanceof Utilizador) {
                $consulta->where(
                    'utilizadores.id',
                    '!=',
                    $nomeado->getKey(),
                );
            }

            $consulta
                ->reorder(
                    'utilizadores.id',
                )
                ->chunkById(
                    self::UTILIZADORES_POR_BLOCO,
                    static function (
                        Collection $destinatarios,
                    ) use (
                        $metalThursday,
                    ): void {
                        Notification::send(
                            $destinatarios,
                            new NotificacaoMetalThursdayCriada(
                                $metalThursday,
                            ),
                        );
                    },
                    'utilizadores.id',
                    'id',
                );
        } catch (Throwable $excecao) {
            report(
                $excecao,
            );
        }
    }
}
