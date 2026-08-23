<?php

declare(strict_types=1);

namespace App\Http\Controllers\MetalThursday;

use App\Enumeracoes\DirecaoOrdenacao;
use App\Enumeracoes\OrdenacaoMetalThursday;
use App\Filtros\FiltrosMetalThursday;
use App\Http\Controllers\Controller;
use App\Http\Requests\MetalThursday\GuardarMetalThursdayRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoUtilizadorNomeado;
use App\Servicos\Incorporacoes\RenderizadorIncorporacoes;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Gere as operações HTTP relacionadas com MetalThursdays.
 *
 * @since 1.0.0
 */
final class ControladorMetalThursday extends Controller
{
    use AuthorizesRequests;

    /**
     * Identificador da vista completa.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const VISTA_COMPLETA = 'completa';

    /**
     * Identificador da vista simplificada.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const VISTA_SIMPLIFICADA = 'simplificada';

    /**
     * Tipos de filtros dinâmicos reconhecidos pela interface.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     */
    private const TIPOS_FILTROS = [
        'selecao',
        'data',
        'sim_nao',
    ];

    /**
     * Chaves das coleções que podem alimentar filtros de seleção.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     */
    private const CHAVES_DADOS_FILTROS = [
        'edicoes',
        'utilizadores',
        'bandas',
        'generos',
    ];

    /**
     * Opções permitidas para o número de registos por página.
     *
     * @var array<int, int>
     *
     * @since 2.0.0
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
     */
    private const POR_PAGINA_PREDEFINIDO = 10;

    /**
     * Primeira estrela apresentada no controlo de avaliação.
     *
     * Cada estrela permite selecionar o valor inteiro ou o respetivo meio
     * valor através do gestor JavaScript do modal.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const PRIMEIRA_ESTRELA_AVALIACAO = 1;

    /**
     * Última estrela apresentada no controlo de avaliação.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const ULTIMA_ESTRELA_AVALIACAO = 10;

    /**
     * Número de utilizadores processados por bloco nas notificações.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const UTILIZADORES_POR_BLOCO = 100;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Cria o controlador.
     *
     * @param  ServicoPersistenciaMetalThursday  $servicoPersistencia  Serviço
     *                                                                 responsável
     *                                                                 pela
     *                                                                 persistência.
     * @param  RenderizadorIncorporacoes  $renderizadorIncorporacoes  Serviço
     *                                                                responsável
     *                                                                pelas
     *                                                                incorporações.
     * @param  ServicoReservasMetalThursday  $servicoReservas  Serviço responsável pelas reservas.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoPersistenciaMetalThursday $servicoPersistencia,
        private readonly RenderizadorIncorporacoes $renderizadorIncorporacoes,
        private readonly ServicoReservasMetalThursday $servicoReservas,
    ) {}

    /**
     * Apresenta a listagem de MetalThursdays.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  FiltrosMetalThursday  $filtros  Serviço de filtros.
     * @return View Página principal.
     *
     * @since 1.0.0
     */
    public function indice(
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

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorAutenticado();

        $registosMetalThursday = null;
        $seccoesSimplificadas = null;

        if ($tipoVista === self::VISTA_SIMPLIFICADA) {
            $seccoesSimplificadas =
                $filtros
                    ->aplicar(
                        $this->criarConsultaSimplificada(),
                    )
                    ->paginate(
                        $porPagina,
                    )
                    ->withQueryString();
        } else {
            $registosMetalThursday =
                $filtros
                    ->aplicar(
                        $this->criarConsultaCompleta(
                            $identificadorUtilizador,
                        ),
                    )
                    ->paginate(
                        $porPagina,
                    )
                    ->withQueryString();
        }

        $dadosControlosListagem =
            $this->obterDadosControlosListagem(
                $pedido,
                $tipoVista,
                $porPagina,
            );

        return view(
            'metal-thursday.indice',
            [
                'registosMetalThursday' => $registosMetalThursday,

                'seccoesSimplificadas' => $seccoesSimplificadas,

                'configuracaoListagemMetalThursday' => $this->obterConfiguracaoListagemMetalThursday(
                    $dadosControlosListagem['gruposFiltrosDisponiveis'],
                ),

                ...$dadosControlosListagem,

                ...$this->obterDadosAvaliacao(),
            ],
        );
    }

    /**
     * Apresenta o formulário de criação de uma MetalThursday.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return View Página de criação.
     *
     * @since 1.0.0
     */
    public function criar(
        Request $pedido,
    ): View {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        return view(
            'metal-thursday.criar',
            [
                ...$this->obterDadosFormulario(),

                'seccoesFormulario' => $this->obterSeccoesAnteriores(
                    $pedido,
                ),

                'configuracaoFormularioMetalThursday' => $this->obterConfiguracaoFormulario(),
            ],
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
     */
    public function guardar(
        GuardarMetalThursdayRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        $identificadorCriador =
            $this->obterIdentificadorUtilizadorAutenticado();

        $resultadoCriacao =
            $this->servicoPersistencia
                ->criarComResultado(
                    $pedido->validated(),
                );

        $metalThursday =
            $resultadoCriacao->obterMetalThursday();

        $reservaSeguinte =
            $resultadoCriacao->obterReservaSeguinte();

        $this->notificarCriacao(
            $metalThursday,
            $identificadorCriador,
            $reservaSeguinte,
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

        return to_route(
            'inicio',
        )->with(
            'sucesso',
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
     */
    public function detalhes(
        MetalThursday $metalThursday,
    ): View {
        $this->authorize(
            'view',
            $metalThursday,
        );

        $this->carregarDetalhes(
            $metalThursday,
            $this->obterIdentificadorUtilizadorAutenticado(),
        );

        return view(
            'metal-thursday.detalhes',
            [
                'metalThursday' => $metalThursday,

                ...$this->obterDadosAvaliacao(),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de uma MetalThursday.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  MetalThursday  $metalThursday  MetalThursday editada.
     * @return View Página de edição.
     *
     * @since 1.0.0
     */
    public function editar(
        Request $pedido,
        MetalThursday $metalThursday,
    ): View {
        $this->authorize(
            'update',
            $metalThursday,
        );

        $metalThursday->loadMissing(
            'seccoes',
        );

        return view(
            'metal-thursday.editar',
            [
                ...$this->obterDadosFormulario(
                    $metalThursday,
                ),

                'seccoesFormulario' => $this->obterSeccoesFormulario(
                    $pedido,
                    $metalThursday,
                ),

                'configuracaoFormularioMetalThursday' => $this->obterConfiguracaoFormulario(),
            ],
        );
    }

    /**
     * Atualiza uma MetalThursday através do serviço de persistência.
     *
     * O serviço bloqueia a MetalThursday e as respetivas secções dentro da
     * transação e devolve a instância final persistida.
     *
     * @param  GuardarMetalThursdayRequest  $pedido  Pedido validado.
     * @param  MetalThursday  $metalThursday  MetalThursday atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function atualizar(
        GuardarMetalThursdayRequest $pedido,
        MetalThursday $metalThursday,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $metalThursday,
        );

        $metalThursdayAtualizada =
            $this->servicoPersistencia
                ->atualizar(
                    $metalThursday,
                    $pedido->validated(),
                );

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'MetalThursday atualizada com sucesso.',

                'metal_thursday' => $this->serializarMetalThursday(
                    $metalThursdayAtualizada,
                ),
            ]);
        }

        return to_route(
            'inicio',
        )->with(
            'sucesso',
            'MetalThursday atualizada com sucesso.',
        );
    }

    /**
     * Elimina logicamente uma MetalThursday.
     *
     * A autorização é verificada antes de abrir a transação. O registo é
     * novamente obtido e bloqueado imediatamente antes da eliminação.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  MetalThursday  $metalThursday  MetalThursday eliminada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function eliminar(
        Request $pedido,
        MetalThursday $metalThursday,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $metalThursday,
        );

        DB::transaction(
            function () use (
                $metalThursday,
            ): void {
                $metalThursdayBloqueada =
                    MetalThursday::query()
                        ->whereKey(
                            $metalThursday->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $metalThursdayBloqueada->deleteOrFail();
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return to_route(
            'inicio',
        )->with(
            'sucesso',
            'MetalThursday eliminada com sucesso.',
        );
    }

    /**
     * Obtém o utilizador há mais tempo sem ser nomeado.
     *
     * O histórico é determinado exclusivamente pelas reservas atribuídas.
     * Quando é recebido um identificador a excluir, esse utilizador não é
     * considerado.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return JsonResponse Identificador do utilizador encontrado.
     *
     * @since 1.0.0
     */
    public function obterUtilizadorHaMaisTempoSemNomeacao(
        Request $pedido,
    ): JsonResponse {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        $identificadorExcluido =
            $pedido->integer(
                'excluir_utilizador_id',
            );

        $utilizador =
            $this->servicoReservas
                ->obterUtilizadorHaMaisTempoSemNomeacao(
                    $identificadorExcluido > 0
                        ? $identificadorExcluido
                        : null,
                );

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
     * @param  int  $identificadorUtilizador  Utilizador autenticado.
     * @return Builder<MetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     */
    private function criarConsultaCompleta(
        int $identificadorUtilizador,
    ): Builder {
        return MetalThursday::query()
            ->comNumeroSemanaNaEdicao()
            ->withCount([
                'comentarios',
                'avaliacoes',
                'audicoes',
            ])
            ->withAvg(
                'avaliacoes',
                'pontuacao',
            )
            ->with(
                $this->obterRelacoesApresentacao(
                    $identificadorUtilizador,
                ),
            );
    }

    /**
     * Cria a consulta da vista simplificada.
     *
     * @return Builder<SeccaoMetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     */
    private function criarConsultaSimplificada(): Builder
    {
        return SeccaoMetalThursday::query()
            ->select([
                'id',
                'metal_thursday_id',
                'tipo_seccao_id',
                'banda_id',
                'titulo',
                'ligacao',
                'ano',
            ])
            ->withCount([
                'avaliacoes',
                'audicoes',
            ])
            ->withAvg(
                'avaliacoes',
                'pontuacao',
            )
            ->with([
                'metalThursday:id,autor_id,data',
                'metalThursday.autor:id,nome',
                'banda:id,nome,origem_geografica_id',
                'banda.origemGeografica:id,nome',
                'banda.generos:id,nome',
                'tipoSeccao:id,nome',
                'avaliacoes.utilizador:id,nome',
                'audicoes.utilizador:id,nome',
            ])
            ->whereHas(
                'tipoSeccao',
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->where(
                    'exige_detalhes',
                    true,
                ),
            );
    }

    /**
     * Carrega as relações necessárias para a página de detalhes.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday carregada.
     * @param  int  $identificadorUtilizador  Utilizador autenticado.
     *
     * @since 2.0.0
     */
    private function carregarDetalhes(
        MetalThursday $metalThursday,
        int $identificadorUtilizador,
    ): void {
        $metalThursday
            ->carregarNumeroSemanaNaEdicao()
            ->loadCount([
                'comentarios',
                'avaliacoes',
                'audicoes',
            ])
            ->loadAvg(
                'avaliacoes',
                'pontuacao',
            )
            ->load(
                $this->obterRelacoesApresentacao(
                    $identificadorUtilizador,
                ),
            );
    }

    /**
     * Obtém a árvore de relações utilizada na vista completa e nos detalhes.
     *
     * Centralizar a configuração evita que a listagem e a página de detalhes
     * evoluam com relações ou agregados diferentes por engano.
     *
     * @param  int  $identificadorUtilizador  Utilizador autenticado.
     * @return array<int|string, mixed> Relações e restrições de eager loading.
     *
     * @since 2.0.0
     */
    private function obterRelacoesApresentacao(
        int $identificadorUtilizador,
    ): array {
        return [
            'edicao:id,nome',
            'autor:id,nome',
            'proximoNomeado:id,nome',
            'avaliacoes.utilizador:id,nome',
            'audicoes.utilizador:id,nome',
            'avaliacaoUtilizadorAutenticado',
            'audicaoUtilizadorAutenticado',

            'comentarios' => function (
                Relation $relacao,
            ) use (
                $identificadorUtilizador,
            ): void {
                $this->configurarComentariosParaApresentacao(
                    $relacao->getQuery(),
                    $identificadorUtilizador,
                );
            },

            'seccoes' => function (
                Relation $relacao,
            ) use (
                $identificadorUtilizador,
            ): void {
                $construtor =
                    $relacao->getQuery();

                $construtor
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
                        'tipoSeccao:id,nome,exige_detalhes',
                        'banda:id,nome',
                        'avaliacoes.utilizador:id,nome',
                        'audicoes.utilizador:id,nome',
                        'avaliacaoUtilizadorAutenticado',
                        'audicaoUtilizadorAutenticado',

                        'comentarios' => function (
                            Relation $relacaoComentarios,
                        ) use (
                            $identificadorUtilizador,
                        ): void {
                            $this->configurarComentariosParaApresentacao(
                                $relacaoComentarios->getQuery(),
                                $identificadorUtilizador,
                            );
                        },
                    ]);
            },
        ];
    }

    /**
     * Obtém os dados comuns aos formulários.
     *
     * @param  MetalThursday|null  $metalThursday  Registo editado ou nulo
     *                                             durante a criação.
     * @return array<string, mixed> Dados dos formulários.
     *
     * @since 2.0.0
     */
    private function obterDadosFormulario(
        ?MetalThursday $metalThursday = null,
    ): array {
        $utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();

        $possuiPrivilegiosAdministrativos =
            $utilizadorAutenticado
                ->possuiPrivilegiosAdministrativos();

        $reservaPendente =
            ! $possuiPrivilegiosAdministrativos
            && ! $metalThursday instanceof MetalThursday
            ? $this
                ->servicoReservas
                ->obterReservaPendenteDoUtilizador(
                    $utilizadorAutenticado,
                )
            : null;

        return [
            'metalThursday' => $metalThursday,

            'utilizadorAutenticado' => $utilizadorAutenticado,

            'podeSelecionarAutor' => $possuiPrivilegiosAdministrativos,

            'podeAlterarData' => $possuiPrivilegiosAdministrativos,

            'autorFormulario' => $metalThursday instanceof MetalThursday
                ? $metalThursday->autor
                : $utilizadorAutenticado,

            'edicoes' => $this->obterEdicoesParaSelecao(),

            'utilizadoresAutores' => $this->obterUtilizadoresParaSelecao(),

            'utilizadoresElegiveisNomeacao' => $this->obterUtilizadoresElegiveisNomeacao(
                $metalThursday,
            ),

            'reservaPendente' => $reservaPendente,

            'tiposSeccao' => TipoSeccao::query()
                ->select([
                    'id',
                    'nome',
                    'descricao',
                    'exige_detalhes',
                ])
                ->orderBy(
                    'ordem',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'bandas' => $this->obterBandasParaSelecao(),

            'origensGeograficas' => OrigemGeografica::query()
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
                ->get(),

            'generos' => $this->obterGenerosParaSelecao(),
        ];
    }

    /**
     * Obtém os dados necessários ao modal de avaliação.
     *
     * Os limites representam as estrelas inteiras apresentadas. A seleção de
     * meios valores é tratada pelo gestor JavaScript do modal.
     *
     * @return array{
     *     pontuacaoMinima: int,
     *     pontuacaoMaxima: int,
     *     pontuacoesDisponiveis: array<int, int>
     * } Dados do controlo de avaliação.
     *
     * @since 2.0.0
     */
    private function obterDadosAvaliacao(): array
    {
        return [
            'pontuacaoMinima' => self::PRIMEIRA_ESTRELA_AVALIACAO,

            'pontuacaoMaxima' => self::ULTIMA_ESTRELA_AVALIACAO,

            'pontuacoesDisponiveis' => range(
                self::PRIMEIRA_ESTRELA_AVALIACAO,
                self::ULTIMA_ESTRELA_AVALIACAO,
            ),
        ];
    }

    /**
     * Obtém as edições disponíveis para seleção.
     *
     * @return Collection<int, Edicao> Edições.
     *
     * @since 2.0.0
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
     * Obtém os utilizadores disponíveis para seleção geral.
     *
     * A disponibilidade voluntária para nomeação não condiciona a seleção
     * como autor nem a utilização nos filtros da listagem.
     *
     * @return Collection<int, Utilizador> Utilizadores.
     *
     * @since 2.0.0
     */
    private function obterUtilizadoresParaSelecao(): Collection
    {
        return Utilizador::query()
            ->comAcessoAtivo()
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
     * Obtém os utilizadores disponíveis para uma nova nomeação.
     *
     * Durante a edição, o nomeado atualmente persistido continua disponível
     * para permitir conservar uma nomeação anteriormente válida, mesmo que
     * tenha entretanto deixado de ser elegível para novas nomeações.
     *
     * @param  MetalThursday|null  $metalThursday  Registo atualmente editado.
     * @return Collection<int, Utilizador> Utilizadores.
     *
     * @since 2.0.0
     */
    private function obterUtilizadoresElegiveisNomeacao(
        ?MetalThursday $metalThursday = null,
    ): Collection {
        $construtorElegiveis = Utilizador::query()
            ->elegiveisParaNomeacao()
            ->select([
                'id',
            ])
            ->reorder();

        $construtor = Utilizador::query()
            ->whereIn(
                'id',
                $construtorElegiveis,
            );

        $identificadorNomeadoAtual =
            $metalThursday?->proximo_nomeado_id;

        if (
            is_numeric(
                $identificadorNomeadoAtual,
            )
            && (int) $identificadorNomeadoAtual > 0
        ) {
            $construtor->orWhere(
                'utilizadores.id',
                (int) $identificadorNomeadoAtual,
            );
        }

        return $construtor
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
     * Obtém as bandas disponíveis para seleção.
     *
     * @return Collection<int, Banda> Bandas.
     *
     * @since 2.0.0
     */
    private function obterBandasParaSelecao(): Collection
    {
        return Banda::query()
            ->select([
                'id',
                'nome',
                'origem_geografica_id',
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
     * @since 2.0.0
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
            trim(
                $valor,
            ),
        )) {
            self::VISTA_SIMPLIFICADA => self::VISTA_SIMPLIFICADA,

            default => self::VISTA_COMPLETA,
        };
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        return $utilizador;
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * Todas as rotas deste controlador pertencem ao grupo autenticado da
     * aplicação, pelo que a ausência de um utilizador válido representa uma
     * inconsistência do contexto HTTP.
     *
     * @return int Identificador validado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorUtilizadorAutenticado(): int
    {
        $identificador =
            $this->obterUtilizadorAutenticado()
                ->getKey();

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
     *     data: string
     * } Dados da MetalThursday.
     *
     * @throws LogicException Quando a MetalThursday contém dados persistidos
     *                        inválidos.
     *
     * @since 2.0.0
     */
    private function serializarMetalThursday(
        MetalThursday $metalThursday,
    ): array {
        $identificador =
            $metalThursday->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                'A MetalThursday não possui um identificador persistido válido.',
            );
        }

        $nome =
            $metalThursday->nome;

        if (
            $nome !== null
            && (
                ! is_string($nome)
                || trim($nome) === ''
            )
        ) {
            throw new LogicException(
                'A MetalThursday não possui um nome persistido válido.',
            );
        }

        $data =
            $metalThursday->data;

        if (! $data instanceof CarbonInterface) {
            throw new LogicException(
                'A MetalThursday não possui uma data persistida válida.',
            );
        }

        return [
            'id' => (int) $identificador,

            'nome' => $nome,

            'data' => $data->format(
                'Y-m-d',
            ),
        ];
    }

    /**
     * Envia as notificações relativas à criação.
     *
     * Uma falha no envio não transforma uma criação já persistida numa
     * resposta de erro.
     *
     * As relações necessárias às notificações são carregadas antes do envio.
     * As permissões de e-mail dos destinatários são obtidas uma vez por bloco,
     * evitando consultas individuais durante a determinação dos canais.
     *
     * A notificação geral utiliza um retrato escalar e é criada uma única vez,
     * sendo reutilizada em todos os blocos de destinatários.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     * @param  int  $identificadorCriador  Criador autenticado.
     * @param  ReservaMetalThursday|null  $reservaSeguinte  Reserva seguinte
     *                                                      criada nesta
     *                                                      publicação.
     *
     * @since 2.0.0
     */
    private function notificarCriacao(
        MetalThursday $metalThursday,
        int $identificadorCriador,
        ?ReservaMetalThursday $reservaSeguinte,
    ): void {
        $metalThursday->load([
            'edicao',
            'autor',
            'criadoPor',
        ]);

        if ($reservaSeguinte instanceof ReservaMetalThursday) {
            $reservaSeguinte->loadMissing([
                'responsavel.permissoesEmail',
            ]);
        }

        $nomeado =
            $reservaSeguinte instanceof ReservaMetalThursday
            ? $reservaSeguinte->responsavel
            : null;

        if ($nomeado instanceof Utilizador) {
            try {
                $nomeado->notify(
                    new NotificacaoUtilizadorNomeado(
                        $reservaSeguinte,
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
            $notificacaoCriacao =
                new NotificacaoMetalThursdayCriada(
                    $metalThursday,
                );

            $construtor = Utilizador::query()
                ->with([
                    'permissoesEmail',
                ])
                ->selecionaveis()
                ->where(
                    'utilizadores.id',
                    '!=',
                    $identificadorCriador,
                );

            if ($nomeado instanceof Utilizador) {
                $construtor->where(
                    'utilizadores.id',
                    '!=',
                    $nomeado->getKey(),
                );
            }

            $construtor
                ->reorder(
                    'utilizadores.id',
                )
                ->chunkById(
                    self::UTILIZADORES_POR_BLOCO,
                    static function (
                        Collection $destinatarios,
                    ) use (
                        $notificacaoCriacao,
                    ): void {
                        Notification::send(
                            $destinatarios,
                            $notificacaoCriacao,
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

    /**
     * Configura a consulta da árvore de comentários para apresentação.
     *
     * As respostas a respostas são associadas pelo fluxo de escrita ao
     * comentário principal, pelo que a interface utiliza apenas dois níveis.
     * A relação `respostas` é carregada nas respostas para satisfazer o
     * contrato do componente de comentário.
     *
     * @param  Builder<Comentario>  $construtor  Consulta dos comentários.
     * @param  int  $identificadorUtilizador  Utilizador autenticado.
     *
     * @since 2.0.0
     */
    private function configurarComentariosParaApresentacao(
        Builder $construtor,
        int $identificadorUtilizador,
    ): void {
        $construtor
            ->principais()
            ->comDadosApresentacao(
                $identificadorUtilizador,
            )
            ->ordenadosCronologicamente()
            ->with([
                'respostas' => static function (
                    Relation $relacao,
                ) use (
                    $identificadorUtilizador,
                ): void {
                    $construtorRespostas =
                        $relacao->getQuery();

                    $construtorRespostas
                        ->comDadosApresentacao(
                            $identificadorUtilizador,
                        )
                        ->ordenadosCronologicamente()
                        ->with(
                            'respostas',
                        );
                },
            ]);
    }

    /**
     * Prepara os controlos utilizados na listagem de MetalThursdays.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  string  $tipoVista  Tipo de vista selecionado.
     * @param  int  $porPagina  Número de resultados por página.
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
     *     opcoesPorPagina: array<int, int>,
     *     porPagina: int,
     *     nomeParametroVista: string,
     *     vistaAtual: string,
     *     vistaCompleta: string,
     *     vistaSimplificada: string,
     *     nomeParametroPorPagina: string,
     *     nomeParametroOrdenacao: string,
     *     ordenacaoAtual: string,
     *     opcoesOrdenacao: array<int, array{
     *         chave: string,
     *         valor: string
     *     }>,
     *     nomeParametroDirecaoOrdenacao: string,
     *     direcaoOrdenacaoAtual: string,
     *     opcoesDirecaoOrdenacao: array<int, array{
     *         chave: string,
     *         valor: string
     *     }>,
     *     textoBotaoAlternarVista: string,
     *     ligacaoLimparFiltros: string
     * } Dados preparados.
     *
     * @since 2.0.0
     */
    private function obterDadosControlosListagem(
        Request $pedido,
        string $tipoVista,
        int $porPagina,
    ): array {
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
            'gruposFiltrosDisponiveis' => $this->obterGruposFiltrosDisponiveis(),

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

    /**
     * Obtém os grupos de filtros disponíveis para apresentação.
     *
     * Entradas inválidas, chaves repetidas e grupos sem filtros válidos não
     * são apresentados.
     *
     * @return array<int, array{
     *     rotulo: string,
     *     filtros: array<int, array{
     *         chave: string,
     *         rotulo: string,
     *         parametro: string,
     *         tipo: 'selecao'|'data'|'sim_nao',
     *         chaveDados: string|null
     *     }>
     * }> Grupos normalizados.
     *
     * @since 2.0.0
     */
    private function obterGruposFiltrosDisponiveis(): array
    {
        $configuracao =
            config(
                'filtros.metal_thursday',
                [],
            );

        if (! is_array($configuracao)) {
            return [];
        }

        $gruposNormalizados = [];
        $chavesUtilizadas = [];

        foreach ($configuracao as $grupo) {
            if (! is_array($grupo)) {
                continue;
            }

            $rotuloGrupo =
                is_string(
                    $grupo['rotulo']
                        ?? null,
                )
                ? trim(
                    $grupo['rotulo'],
                )
                : '';

            $filtrosConfigurados =
                $grupo['filtros']
                ?? [];

            if (! is_array($filtrosConfigurados)) {
                continue;
            }

            $filtrosNormalizados = [];

            foreach ($filtrosConfigurados as $filtro) {
                if (! is_array($filtro)) {
                    continue;
                }

                $chave =
                    is_string(
                        $filtro['chave']
                            ?? null,
                    )
                    ? trim(
                        $filtro['chave'],
                    )
                    : '';

                $rotulo =
                    is_string(
                        $filtro['rotulo']
                            ?? null,
                    )
                    ? trim(
                        $filtro['rotulo'],
                    )
                    : '';

                $parametro =
                    is_string(
                        $filtro['parametro']
                            ?? null,
                    )
                    ? trim(
                        $filtro['parametro'],
                    )
                    : '';

                $tipo =
                    is_string(
                        $filtro['tipo']
                            ?? null,
                    )
                    ? trim(
                        $filtro['tipo'],
                    )
                    : '';

                $chaveDadosRecebida =
                    $filtro['chaveDados']
                    ?? null;

                $chaveDados =
                    is_string($chaveDadosRecebida)
                    ? trim($chaveDadosRecebida)
                    : null;

                if (
                    $chave === ''
                    || $rotulo === ''
                    || $parametro === ''
                    || isset($chavesUtilizadas[$chave])
                    || ! in_array(
                        $tipo,
                        self::TIPOS_FILTROS,
                        true,
                    )
                ) {
                    continue;
                }

                if ($tipo === 'selecao') {
                    if (
                        $chaveDados === null
                        || ! in_array(
                            $chaveDados,
                            self::CHAVES_DADOS_FILTROS,
                            true,
                        )
                    ) {
                        continue;
                    }
                } else {
                    $chaveDados = null;
                }

                $chavesUtilizadas[$chave] = true;

                $filtrosNormalizados[] = [
                    'chave' => $chave,

                    'rotulo' => $rotulo,

                    'parametro' => $parametro,

                    'tipo' => $tipo,

                    'chaveDados' => $chaveDados,
                ];
            }

            if ($filtrosNormalizados === []) {
                continue;
            }

            $gruposNormalizados[] = [
                'rotulo' => $rotuloGrupo !== ''
                    ? $rotuloGrupo
                    : 'Filtros',

                'filtros' => $filtrosNormalizados,
            ];
        }

        return $gruposNormalizados;
    }

    /**
     * Obtém as secções anteriormente submetidas.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return array<int, array<string, mixed>> Secções anteriores normalizadas.
     *
     * @since 2.0.0
     */
    private function obterSeccoesAnteriores(
        Request $pedido,
    ): array {
        return $this->normalizarSeccoesSubmetidas(
            $pedido->old(
                'seccoes',
                [],
            ),
        );
    }

    /**
     * Obtém as secções apresentadas no formulário de edição.
     *
     * Quando existe uma submissão anterior inválida, são utilizados os dados
     * guardados na sessão. Caso contrário, são utilizados os modelos
     * associados à MetalThursday.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  MetalThursday  $metalThursday  MetalThursday editada.
     * @return array<int, SeccaoMetalThursday|array<string, mixed>> Secções
     *                                                              utilizadas pelo formulário.
     *
     * @since 2.0.0
     */
    private function obterSeccoesFormulario(
        Request $pedido,
        MetalThursday $metalThursday,
    ): array {
        if (
            ! $pedido
                ->session()
                ->exists(
                    '_old_input.seccoes',
                )
        ) {
            return $metalThursday
                ->seccoes
                ->values()
                ->all();
        }

        return $this->normalizarSeccoesSubmetidas(
            $pedido->old(
                'seccoes',
                [],
            ),
        );
    }

    /**
     * Normaliza as secções submetidas anteriormente.
     *
     * Apenas são mantidas entradas com índices numéricos não negativos e
     * valores estruturados como arrays.
     *
     * @param  mixed  $seccoes  Valor recebido da sessão.
     * @return array<int, array<string, mixed>> Secções normalizadas.
     *
     * @since 2.0.0
     */
    private function normalizarSeccoesSubmetidas(
        mixed $seccoes,
    ): array {
        if (! is_array($seccoes)) {
            return [];
        }

        $seccoesNormalizadas = [];

        foreach ($seccoes as $indice => $seccao) {
            if (! is_array($seccao)) {
                continue;
            }

            if (
                ! is_int($indice)
                && (
                    ! is_string($indice)
                    || ! ctype_digit($indice)
                )
            ) {
                continue;
            }

            $indiceNormalizado =
                (int) $indice;

            if ($indiceNormalizado < 0) {
                continue;
            }

            $seccoesNormalizadas[$indiceNormalizado] =
                $seccao;
        }

        ksort(
            $seccoesNormalizadas,
        );

        return $seccoesNormalizadas;
    }

    /**
     * Obtém a configuração necessária aos formulários dinâmicos.
     *
     * @return array{
     *     enderecos: array{
     *         guardarEdicao: string,
     *         guardarBanda: string,
     *         guardarGenero: string,
     *         obterUtilizadorHaMaisTempoSemNomeacao: string
     *     },
     *     fornecedoresIncorporacao: array<int, array{
     *         tipo: string,
     *         etiqueta: string,
     *         expressao_regular: string
     *     }>
     * } Configuração dos formulários.
     *
     * @since 2.0.0
     */
    private function obterConfiguracaoFormulario(): array
    {
        return [
            'enderecos' => [
                'guardarEdicao' => route(
                    'edicoes.guardar',
                ),

                'guardarBanda' => route(
                    'bandas.guardar',
                ),

                'guardarGenero' => route(
                    'generos.guardar',
                ),

                'obterUtilizadorHaMaisTempoSemNomeacao' => route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                ),
            ],

            'fornecedoresIncorporacao' => $this
                ->renderizadorIncorporacoes
                ->definicoesParaJavaScript(),
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
     *         bandas: array<int, array{
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
    private function obterConfiguracaoListagemMetalThursday(
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
                'edicoes' => $this->serializarOpcoesSelecao(
                    $this->obterEdicoesParaSelecao(),
                ),

                'utilizadores' => $this->serializarOpcoesSelecao(
                    $this->obterUtilizadoresParaSelecao(),
                ),

                'bandas' => $this->serializarOpcoesSelecao(
                    $this->obterBandasParaSelecao(),
                ),

                'generos' => $this->serializarOpcoesSelecao(
                    $this->obterGenerosParaSelecao(),
                ),
            ],

            'filtrosDisponiveis' => $filtrosPorChave,

            'vistas' => [
                'completa' => self::VISTA_COMPLETA,

                'simplificada' => self::VISTA_SIMPLIFICADA,
            ],
        ];
    }

    /**
     * Converte modelos nomeados em opções simples para o JavaScript.
     *
     * @param  Collection<int, Model>  $modelos  Modelos convertidos.
     * @return array<int, array{
     *     identificador: int,
     *     nome: string
     * }> Opções preparadas.
     *
     * @throws LogicException Quando um modelo não possui identificador ou nome
     *                        válidos.
     *
     * @since 2.0.0
     */
    private function serializarOpcoesSelecao(
        Collection $modelos,
    ): array {
        $opcoes = [];

        foreach ($modelos as $modelo) {
            $identificador =
                $modelo->getKey();

            $nome =
                $modelo->getAttribute(
                    'nome',
                );

            if (
                ! is_numeric($identificador)
                || (int) $identificador < 1
            ) {
                throw new LogicException(
                    sprintf(
                        'O modelo %s não possui um identificador válido.',
                        $modelo::class,
                    ),
                );
            }

            if (
                ! is_string($nome)
                || trim($nome) === ''
            ) {
                throw new LogicException(
                    sprintf(
                        'O modelo %s não possui um nome válido.',
                        $modelo::class,
                    ),
                );
            }

            $opcoes[] = [
                'identificador' => (int) $identificador,

                'nome' => trim(
                    $nome,
                ),
            ];
        }

        return $opcoes;
    }
}
