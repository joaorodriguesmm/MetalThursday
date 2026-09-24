<?php

declare(strict_types=1);

namespace App\Http\Controllers\MetalThursday;

use App\Filtros\FiltrosMetalThursday;
use App\Http\Controllers\Controller;
use App\Http\Middleware\MetalThursday\ExigirCriacaoAdministrativaMetalThursday;
use App\Http\Requests\MetalThursday\GuardarMetalThursdayRequest;
use App\Http\Requests\MetalThursday\GuardarRascunhoMetalThursdayRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoUtilizadorNomeado;
use App\Resultados\MetalThursday\MetalThursdayCriada;
use App\Servicos\MetalThursday\ServicoConfiguracaoInterfaceMetalThursday;
use App\Servicos\MetalThursday\ServicoControlosListagemMetalThursday;
use App\Servicos\MetalThursday\ServicoNotificacaoPublicacaoMetalThursday;
use App\Servicos\MetalThursday\ServicoOpcoesMetalThursday;
use App\Servicos\MetalThursday\ServicoParametrosListagemMetalThursday;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use App\Servicos\MetalThursday\ServicoPreparacaoMetalThursday;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Gere as operações HTTP relacionadas com MetalThursdays.
 *
 * @since 1.0.0
 */
final class ControladorMetalThursday extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Define o middleware aplicado diretamente às ações do controlador.
     *
     * O fluxo genérico de criação fica reservado à administração. A preparação
     * e a submissão de uma reserva utilizam ações distintas e não recebem este
     * middleware.
     *
     * @return array<int, Middleware|string> Middleware do controlador.
     *
     * @since 2.0.0
     */
    public static function middleware(): array
    {
        return [
            new Middleware(
                ExigirCriacaoAdministrativaMetalThursday::class,
                only: [
                    'criar',
                    'guardar',
                ],
            ),
        ];
    }

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
     * @param  ServicoReservasMetalThursday  $servicoReservas  Serviço responsável
     *                                                         pelas reservas.
     * @param  ServicoPreparacaoMetalThursday  $servicoPreparacao  Serviço
     *                                                             responsável pela
     *                                                             preparação.
     * @param  ServicoNotificacaoPublicacaoMetalThursday  $servicoNotificacaoPublicacao
     *                                                                                   Serviço
     *                                                                                   responsável
     *                                                                                   pela notificação
     *                                                                                   da publicação.
     * @param  ServicoOpcoesMetalThursday  $servicoOpcoes  Serviço responsável
     *                                                     pelas opções de leitura.
     * @param  ServicoConfiguracaoInterfaceMetalThursday  $servicoConfiguracaoInterface
     *                                                                                   Serviço responsável
     *                                                                                   pela configuração
     *                                                                                   de frontend.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoPersistenciaMetalThursday $servicoPersistencia,
        private readonly ServicoReservasMetalThursday $servicoReservas,
        private readonly ServicoPreparacaoMetalThursday $servicoPreparacao,
        private readonly ServicoNotificacaoPublicacaoMetalThursday $servicoNotificacaoPublicacao,
        private readonly ServicoOpcoesMetalThursday $servicoOpcoes,
        private readonly ServicoConfiguracaoInterfaceMetalThursday $servicoConfiguracaoInterface,
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
        ServicoParametrosListagemMetalThursday $servicoParametrosListagem,
        ServicoControlosListagemMetalThursday $servicoControlosListagem,
    ): View {
        $this->authorize(
            'viewAny',
            MetalThursday::class,
        );

        $porPagina =
            $servicoParametrosListagem->obterNumeroPorPagina(
                $pedido,
            );

        $tipoVista =
            $servicoParametrosListagem->obterTipoVista(
                $pedido,
            );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorAutenticado();

        $registosMetalThursday = null;
        $seccoesSimplificadas = null;

        if (
            $tipoVista
            === ServicoParametrosListagemMetalThursday::VISTA_SIMPLIFICADA
        ) {
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
            $servicoControlosListagem->obterDados(
                $pedido,
                $tipoVista,
                $porPagina,
            );

        return view(
            'metal-thursday.indice',
            [
                'registosMetalThursday' => $registosMetalThursday,

                'seccoesSimplificadas' => $seccoesSimplificadas,

                'configuracaoListagemMetalThursday' => $this
                    ->servicoConfiguracaoInterface
                    ->obterConfiguracaoListagemMetalThursday(
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

                'modoPreparacaoReserva' => false,

                'seccoesFormulario' => $this->obterSeccoesAnteriores(
                    $pedido,
                ),

                'configuracaoFormularioMetalThursday' => $this
                    ->servicoConfiguracaoInterface
                    ->obterConfiguracaoFormulario(),
            ],
        );
    }

    /**
     * Apresenta o formulário de preparação de uma reserva pendente.
     *
     * A reserva torna-se a fonte autoritativa para a data e para o autor,
     * mesmo quando o respetivo responsável possui privilégios administrativos.
     *
     * Quando existe um rascunho persistido, os respetivos dados editáveis são
     * utilizados como valores iniciais do formulário. Dados antigos da sessão,
     * resultantes de uma submissão inválida, mantêm sempre precedência.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  ReservaMetalThursday  $reservaMetalThursday  Reserva preparada.
     * @return View Página de preparação.
     *
     * @since 2.0.0
     */
    public function prepararReserva(
        Request $pedido,
        ReservaMetalThursday $reservaMetalThursday,
    ): View {
        $this->authorize(
            'create',
            MetalThursday::class,
        );

        $utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();

        if (
            ! $reservaMetalThursday->estaPendente()
            || ! is_numeric(
                $reservaMetalThursday->responsavel_id,
            )
            || (int) $reservaMetalThursday->responsavel_id
            !== (int) $utilizadorAutenticado->getKey()
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
            );
        }

        $rascunho =
            $reservaMetalThursday
                ->rascunho()
                ->first();

        $dadosRascunho =
            $rascunho instanceof RascunhoMetalThursday
            && is_array(
                $rascunho->dados,
            )
            ? $rascunho->dados
            : [];

        return view(
            'metal-thursday.criar',
            [
                ...$this->obterDadosFormulario(
                    null,
                    $reservaMetalThursday,
                ),

                'modoPreparacaoReserva' => true,

                'dadosRascunhoFormulario' => $dadosRascunho,

                'seccoesFormulario' => $this->obterSeccoesPreparacaoReserva(
                    $pedido,
                    $dadosRascunho,
                ),

                'configuracaoFormularioMetalThursday' => $this
                    ->servicoConfiguracaoInterface
                    ->obterConfiguracaoFormulario(),
            ],
        );
    }

    /**
     * Guarda o rascunho associado a uma reserva pendente.
     *
     * O pedido contém apenas os dados editáveis do formulário. A reserva
     * continua pendente e nenhuma MetalThursday definitiva é criada nesta
     * operação.
     *
     * @param  GuardarRascunhoMetalThursdayRequest  $pedido  Pedido validado.
     * @param  ReservaMetalThursday  $reservaMetalThursday  Reserva preparada.
     * @return RedirectResponse Redirecionamento para a preparação.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    public function guardarRascunhoReserva(
        GuardarRascunhoMetalThursdayRequest $pedido,
        ReservaMetalThursday $reservaMetalThursday,
    ): RedirectResponse {
        $rascunho =
            $this->servicoPreparacao
                ->guardarRascunho(
                    $reservaMetalThursday,
                    $this->obterUtilizadorAutenticado(),
                    $pedido->validated(),
                );

        if (! $rascunho instanceof RascunhoMetalThursday) {
            abort(
                Response::HTTP_FORBIDDEN,
            );
        }

        return redirect()
            ->route(
                'metal-thursday.reservas.preparar',
                $reservaMetalThursday,
            )
            ->with(
                'sucesso',
                'Rascunho guardado com sucesso.',
            );
    }

    /**
     * Finaliza uma MetalThursday através de uma reserva explícita.
     *
     * O middleware e o pedido validado mantêm a reserva como fonte autoritativa
     * da data e do autor. A preparação é finalizada atomicamente e o eventual
     * rascunho só é eliminado depois da persistência definitiva.
     *
     * @param  GuardarMetalThursdayRequest  $pedido  Pedido validado.
     * @param  ReservaMetalThursday  $reservaMetalThursday  Reserva preparada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    public function guardarReserva(
        GuardarMetalThursdayRequest $pedido,
        ReservaMetalThursday $reservaMetalThursday,
    ): JsonResponse|RedirectResponse {
        $responsavel =
            $this->obterUtilizadorAutenticado();

        $resultadoCriacao =
            $this->servicoPreparacao
                ->finalizar(
                    $reservaMetalThursday,
                    $responsavel,
                    $pedido->validated(),
                );

        if (! $resultadoCriacao instanceof MetalThursdayCriada) {
            abort(
                Response::HTTP_FORBIDDEN,
            );
        }

        return $this->responderCriacao(
            $pedido,
            $resultadoCriacao,
            $this->obterMensagemSucessoFinalizacao(
                $resultadoCriacao,
            ),
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

        $resultadoCriacao =
            $this->servicoPersistencia
                ->criarComResultado(
                    $pedido->validated(),
                );

        return $this->responderCriacao(
            $pedido,
            $resultadoCriacao,
            'MetalThursday criada com sucesso.',
        );
    }

    /**
     * Obtém a mensagem correspondente ao estado temporal após a finalização.
     *
     * Uma MetalThursday cuja data ainda não chegou fica preparada. Quando a data
     * corresponde ao dia atual ou já passou, a publicação é imediata.
     *
     * @param  MetalThursdayCriada  $resultadoCriacao  Resultado persistido.
     * @return string Mensagem de sucesso.
     *
     * @throws LogicException Quando a MetalThursday não possui uma data válida.
     *
     * @since 2.0.0
     */
    private function obterMensagemSucessoFinalizacao(
        MetalThursdayCriada $resultadoCriacao,
    ): string {
        $data =
            $resultadoCriacao
                ->obterMetalThursday()
                ->data;

        if (! $data instanceof CarbonInterface) {
            throw new LogicException(
                'A MetalThursday criada não possui uma data válida.',
            );
        }

        $hoje =
            CarbonImmutable::now(
                config(
                    'app.timezone',
                ),
            )->format(
                'Y-m-d',
            );

        return $data->format(
            'Y-m-d',
        ) <= $hoje
            ? 'MetalThursday publicada com sucesso.'
            : 'MetalThursday marcada como preparada com sucesso.';
    }

    /**
     * Constrói a resposta comum após a criação de uma MetalThursday.
     *
     * A eventual nomeação seguinte é comunicada imediatamente. A notificação
     * geral da publicação é delegada ao serviço temporal, que apenas a processa
     * quando a data da MetalThursday já tiver chegado.
     *
     * @param  GuardarMetalThursdayRequest  $pedido  Pedido original.
     * @param  MetalThursdayCriada  $resultadoCriacao  Resultado persistido.
     * @param  string  $mensagemSucesso  Mensagem apresentada.
     * @return JsonResponse|RedirectResponse Resposta HTTP.
     *
     * @since 2.0.0
     */
    private function responderCriacao(
        GuardarMetalThursdayRequest $pedido,
        MetalThursdayCriada $resultadoCriacao,
        string $mensagemSucesso,
    ): JsonResponse|RedirectResponse {
        $metalThursday =
            $resultadoCriacao->obterMetalThursday();

        $reservaSeguinte =
            $resultadoCriacao->obterReservaSeguinte();

        $this->notificarNomeacaoSeguinte(
            $metalThursday,
            $reservaSeguinte,
        );

        $this->notificarPublicacao(
            $metalThursday,
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' => $mensagemSucesso,

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
            $mensagemSucesso,
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
        $utilizador =
            $this->obterUtilizadorAutenticado();

        if (
            ! Gate::forUser(
                $utilizador,
            )->allows(
                'view',
                $metalThursday,
            )
        ) {
            abort(
                Response::HTTP_NOT_FOUND,
            );
        }

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

        $metalThursday->loadMissing([
            'seccoes.lancamento.faixas.musica',
            'seccoes.ligacoes',
        ]);

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

                'configuracaoFormularioMetalThursday' => $this
                    ->servicoConfiguracaoInterface
                    ->obterConfiguracaoFormulario(),
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
            ->publicadas()
            ->comNumeroSemanaNaEdicao()
            ->withCount([
                'comentariosComConteudo as comentarios_count',
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
     * Apenas são consideradas secções pertencentes a MetalThursdays já
     * publicadas.
     *
     * @return Builder<SeccaoMetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     */
    private function criarConsultaSimplificada(): Builder
    {
        return SeccaoMetalThursday::query()
            ->whereHas(
                'metalThursday',
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->publicadas(),
            )
            ->select([
                'id',
                'metal_thursday_id',
                'tipo_seccao_id',
                'artista_id',
                'titulo',
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
                'artista:id,nome,origem_geografica_id',
                'artista.origemGeografica:id,nome',
                'artista.generos:id,nome',
                'tipoSeccao:id,nome',
                'ligacoes:id,seccao_metal_thursday_id,plataforma,etiqueta,url,ordem',
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
     * As MetalThursdays preparadas carregam apenas os dados necessários à
     * apresentação do respetivo conteúdo. As interações sociais só são
     * consultadas depois da publicação.
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
            ->carregarNumeroSemanaNaEdicao();

        if (! $metalThursday->estaPublicada()) {
            $metalThursday->load(
                $this->obterRelacoesApresentacaoPreparada(),
            );

            return;
        }

        $metalThursday
            ->loadCount([
                'comentariosComConteudo as comentarios_count',
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
     * Obtém as relações necessárias para apresentar uma MetalThursday ainda
     * preparada sem consultar dados de interação.
     *
     * @return array<int|string, mixed> Relações necessárias à apresentação.
     *
     * @since 2.0.0
     */
    private function obterRelacoesApresentacaoPreparada(): array
    {
        return [
            'edicao:id,nome',
            'autor:id,nome',
            'proximoNomeado:id,nome',

            'seccoes' => static function (
                Relation $relacao,
            ): void {
                $relacao
                    ->getQuery()
                    ->with([
                        'tipoSeccao:id,nome,exige_detalhes',
                        'artista:id,nome',
                        'ligacoes',
                    ]);
            },
        ];
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
                    $relacao,
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
                        'comentariosComConteudo as comentarios_count',
                        'avaliacoes',
                        'audicoes',
                    ])
                    ->withAvg(
                        'avaliacoes',
                        'pontuacao',
                    )
                    ->with([
                        'tipoSeccao:id,nome,exige_detalhes',
                        'artista:id,nome',
                        'ligacoes',
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
                                $relacaoComentarios,
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
     * @param  ReservaMetalThursday|null  $reservaPreparada  Reserva explícita
     *                                                       preparada ou nula.
     * @return array<string, mixed> Dados dos formulários.
     *
     * @since 2.0.0
     */
    private function obterDadosFormulario(
        ?MetalThursday $metalThursday = null,
        ?ReservaMetalThursday $reservaPreparada = null,
    ): array {
        $utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();

        $possuiPrivilegiosAdministrativos =
            $utilizadorAutenticado
                ->possuiPrivilegiosAdministrativos();

        $estaAPrepararReserva =
            $reservaPreparada instanceof ReservaMetalThursday;

        $reservaPendente =
            $estaAPrepararReserva
            ? $reservaPreparada
            : (
                ! $possuiPrivilegiosAdministrativos
                && ! $metalThursday instanceof MetalThursday
                ? $this
                    ->servicoReservas
                    ->obterReservaPendenteDoUtilizador(
                        $utilizadorAutenticado,
                    )
                : null
            );

        $reservaSeguinte =
            $estaAPrepararReserva
            ? $this->obterReservaSeguinteParaPreparacao(
                $reservaPreparada,
            )
            : null;

        return [
            'metalThursday' => $metalThursday,

            'utilizadorAutenticado' => $utilizadorAutenticado,

            'podeSelecionarAutor' => $possuiPrivilegiosAdministrativos
                && ! $estaAPrepararReserva,

            'podeAlterarData' => $possuiPrivilegiosAdministrativos
                && ! $estaAPrepararReserva,

            'autorFormulario' => $metalThursday instanceof MetalThursday
                ? $metalThursday->autor
                : $utilizadorAutenticado,

            'edicoes' => $this
                ->servicoOpcoes
                ->obterEdicoesParaSelecao(),

            'utilizadoresAutores' => $this
                ->servicoOpcoes
                ->obterUtilizadoresParaSelecao(),

            'utilizadoresElegiveisNomeacao' => $this
                ->servicoOpcoes
                ->obterUtilizadoresElegiveisNomeacao(
                    $metalThursday,
                ),

            'reservaPendente' => $reservaPendente,

            'reservaSeguinte' => $reservaSeguinte,

            'tiposSeccao' => $this
                ->servicoOpcoes
                ->obterTiposSeccao(),

            'artistas' => $this
                ->servicoOpcoes
                ->obterArtistasParaSelecao(
                    $metalThursday,
                ),

            'origensGeograficas' => $this
                ->servicoOpcoes
                ->obterOrigensGeograficas(),

            'generos' => $this
                ->servicoOpcoes
                ->obterGenerosParaSelecao(),
        ];
    }

    /**
     * Obtém a reserva da quinta-feira seguinte à reserva preparada.
     *
     * A consulta é efetuada apenas no fluxo explícito de preparação. Quando o
     * slot seguinte já existe, essa reserva é autoritativa para a nomeação
     * apresentada no formulário.
     *
     * @param  ReservaMetalThursday  $reservaPreparada  Reserva atual.
     * @return ReservaMetalThursday|null Reserva seguinte ou nulo quando ainda
     *                                   não existe.
     *
     * @throws LogicException Quando a reserva preparada não possui uma data
     *                        válida.
     *
     * @since 2.0.0
     */
    private function obterReservaSeguinteParaPreparacao(
        ReservaMetalThursday $reservaPreparada,
    ): ?ReservaMetalThursday {
        $dataReserva =
            $reservaPreparada->data;

        if (! $dataReserva instanceof CarbonInterface) {
            throw new LogicException(
                'A reserva preparada não possui uma data válida.',
            );
        }

        $dataSeguinte = CarbonImmutable::instance(
            $dataReserva,
        )
            ->addWeek()
            ->toDateString();

        return ReservaMetalThursday::query()
            ->select([
                'id',
                'data',
                'responsavel_id',
                'metal_thursday_id',
            ])
            ->with([
                'responsavel:id,nome',
            ])
            ->where(
                'data',
                $dataSeguinte,
            )
            ->first();
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
     * Notifica o responsável pela reserva seguinte criada durante a finalização.
     *
     * A nomeação é um acontecimento operacional independente da publicação. Pode,
     * por isso, ser comunicada mesmo quando a MetalThursday acabada de preparar
     * ainda possui uma data futura.
     *
     * Uma falha no envio não transforma uma criação já persistida numa resposta
     * de erro.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     * @param  ReservaMetalThursday|null  $reservaSeguinte  Reserva seguinte
     *                                                      eventualmente criada.
     *
     * @since 2.0.0
     */
    private function notificarNomeacaoSeguinte(
        MetalThursday $metalThursday,
        ?ReservaMetalThursday $reservaSeguinte,
    ): void {
        if (! $reservaSeguinte instanceof ReservaMetalThursday) {
            return;
        }

        $reservaSeguinte->loadMissing([
            'responsavel.permissoesEmail',
        ]);

        $nomeado =
            $reservaSeguinte->responsavel;

        if (! $nomeado instanceof Utilizador) {
            return;
        }

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

    /**
     * Processa a notificação geral quando a MetalThursday já está publicada.
     *
     * MetalThursdays preparadas com data futura permanecem pendentes para o
     * processamento temporal posterior. Uma falha na notificação não transforma
     * a criação já persistida numa resposta de erro.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function notificarPublicacao(
        MetalThursday $metalThursday,
    ): void {
        try {
            $this->servicoNotificacaoPublicacao
                ->processar(
                    $metalThursday,
                );
        } catch (Throwable $excecao) {
            report(
                $excecao,
            );
        }
    }

    /**
     * Configura a consulta dos comentários principais para apresentação.
     *
     * Apenas os comentários principais são carregados inicialmente e os mais
     * recentes são apresentados primeiro. A quantidade de respostas diretas
     * acompanha cada comentário através do escopo de apresentação, mas os
     * respetivos modelos são obtidos apenas quando o utilizador expande o ramo.
     *
     * @param  Relation  $relacao  Relação dos comentários.
     * @param  int  $identificadorUtilizador  Utilizador autenticado.
     *
     * @throws LogicException Quando a relação não utiliza o modelo esperado.
     *
     * @since 2.0.0
     */
    private function configurarComentariosParaApresentacao(
        Relation $relacao,
        int $identificadorUtilizador,
    ): void {
        $construtor =
            $relacao->getQuery();

        if (! $construtor->getModel() instanceof Comentario) {
            throw new LogicException(
                'A relação de comentários utiliza um modelo inválido.',
            );
        }

        /** @var Builder<Comentario> $construtor */
        $construtor
            ->principais()
            ->comDadosApresentacao(
                $identificadorUtilizador,
            )
            ->ordenadosMaisRecentes();
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
     * Obtém as secções utilizadas durante a preparação de uma reserva.
     *
     * Uma submissão anterior inválida possui precedência sobre o rascunho
     * persistido. Na ausência de dados antigos, são utilizadas as secções do
     * rascunho. Sem qualquer uma das fontes, o formulário inicia-se vazio.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  array<string, mixed>  $dadosRascunho  Dados persistidos.
     * @return array<int, array<string, mixed>> Secções normalizadas.
     *
     * @since 2.0.0
     */
    private function obterSeccoesPreparacaoReserva(
        Request $pedido,
        array $dadosRascunho,
    ): array {
        if (
            $pedido
                ->session()
                ->exists(
                    '_old_input.seccoes',
                )
        ) {
            return $this->obterSeccoesAnteriores(
                $pedido,
            );
        }

        return $this->normalizarSeccoesSubmetidas(
            $dadosRascunho['seccoes']
                ?? [],
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
                && ! ctype_digit($indice)
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
}
