<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\CriarConviteRequest;
use App\Http\Requests\Autenticacao\RevogarConviteRequest;
use App\Models\Autenticacao\Convite;
use App\Servicos\Autenticacao\ServicoConvites;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

/**
 * Gere administrativamente os convites de registo.
 *
 * Permite consultar e filtrar os convites, criar novas ligações e revogar
 * convites ainda não utilizados.
 *
 * O código original é apresentado apenas na resposta imediata da criação e
 * nunca é colocado na sessão.
 *
 * @since 2.0.0
 */
final class ControladorConvite extends Controller
{
    use AuthorizesRequests;

    /**
     * Número de convites apresentados por página.
     *
     * @since 2.0.0
     */
    private const REGISTOS_POR_PAGINA =
        20;

    /**
     * Comprimento máximo da pesquisa.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA =
        100;

    /**
     * Identificador do estado disponível.
     *
     * @since 2.0.0
     */
    private const ESTADO_DISPONIVEL =
        'disponivel';

    /**
     * Identificador do estado expirado.
     *
     * @since 2.0.0
     */
    private const ESTADO_EXPIRADO =
        'expirado';

    /**
     * Identificador do estado revogado.
     *
     * @since 2.0.0
     */
    private const ESTADO_REVOGADO =
        'revogado';

    /**
     * Identificador do estado utilizado.
     *
     * @since 2.0.0
     */
    private const ESTADO_UTILIZADO =
        'utilizado';

    /**
     * Estados disponibilizados pelo filtro e respetivas etiquetas.
     *
     * @var array<string, non-empty-string>
     *
     * @since 2.0.0
     */
    private const ESTADOS_DISPONIVEIS = [
        self::ESTADO_DISPONIVEL => 'Disponível',
        self::ESTADO_EXPIRADO => 'Expirado',
        self::ESTADO_REVOGADO => 'Revogado',
        self::ESTADO_UTILIZADO => 'Utilizado',
    ];

    /**
     * Cria o controlador.
     *
     * @param  ServicoConvites  $servicoConvites  Serviço dos convites.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoConvites $servicoConvites,
    ) {}

    /**
     * Apresenta a lista paginada dos convites.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem administrativa.
     *
     * @since 2.0.0
     */
    public function indice(
        Request $pedido,
    ): View {
        $this->authorize(
            'viewAny',
            Convite::class,
        );

        $pesquisa =
            $this->normalizarPesquisa(
                $pedido->query(
                    'pesquisa',
                ),
            );

        $estado =
            $this->normalizarEstado(
                $pedido->query(
                    'estado',
                ),
            );

        $convites =
            Convite::query()
                ->select([
                    'id',
                    'nome_convidado',
                    'email_destino',
                    'criado_por_id',
                    'utilizado_por_id',
                    'expira_em',
                    'utilizado_em',
                    'revogado_em',
                    'revogado_por_id',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'criador:id,nome,email',

                    'utilizador:id,nome,email',

                    'responsavelRevogacao:id,nome,email',
                ])
                ->when(
                    $pesquisa !== null,
                    static function (
                        Builder $construtor,
                    ) use (
                        $pesquisa,
                    ): void {
                        $construtor->where(
                            static function (
                                Builder $construtorPesquisa,
                            ) use (
                                $pesquisa,
                            ): void {
                                $construtorPesquisa
                                    ->where(
                                        'nome_convidado',
                                        'like',
                                        '%'.$pesquisa.'%',
                                    )
                                    ->orWhere(
                                        'email_destino',
                                        'like',
                                        '%'.$pesquisa.'%',
                                    );
                            },
                        );
                    },
                )
                ->when(
                    $estado === self::ESTADO_DISPONIVEL,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->disponiveis(),
                )
                ->when(
                    $estado === self::ESTADO_EXPIRADO,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor
                        ->pendentes()
                        ->whereNotNull(
                            'expira_em',
                        )
                        ->where(
                            'expira_em',
                            '<=',
                            CarbonImmutable::now(),
                        ),
                )
                ->when(
                    $estado === self::ESTADO_REVOGADO,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->whereNotNull(
                        'revogado_em',
                    ),
                )
                ->when(
                    $estado === self::ESTADO_UTILIZADO,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->whereNotNull(
                        'utilizado_em',
                    ),
                )
                ->orderByDesc(
                    'created_at',
                )
                ->orderByDesc(
                    'id',
                )
                ->paginate(
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'convites.indice',
            [
                'convites' => $convites,

                'pesquisaAtual' => $pesquisa,

                'estadoAtual' => $estado,

                'estadosDisponiveis' => self::ESTADOS_DISPONIVEIS,

                'filtrosAtivos' => $pesquisa !== null
                    || $estado !== null,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação.
     *
     * @return View Formulário administrativo.
     *
     * @since 2.0.0
     */
    public function criar(): View
    {
        $this->authorize(
            'create',
            Convite::class,
        );

        return view(
            'convites.criar',
            [
                'expiracaoMinima' => CarbonImmutable::now()
                    ->addMinute()
                    ->startOfMinute()
                    ->format(
                        'Y-m-d\\TH:i',
                    ),
            ],
        );
    }

    /**
     * Guarda um novo convite e apresenta imediatamente a ligação.
     *
     * A resposta não é armazenável em cache e não utiliza redirecionamento,
     * porque o resultado sensível da criação não pode ser serializado.
     *
     * @param  CriarConviteRequest  $pedido  Pedido validado.
     * @return Response Página com a ligação original.
     *
     * @throws Throwable Quando ocorre um erro inesperado na criação.
     *
     * @since 2.0.0
     */
    public function guardar(
        CriarConviteRequest $pedido,
    ): Response {
        $resultado =
            $this
                ->servicoConvites
                ->criar(
                    nomeConvidado: $pedido->obterNomeConvidado(),
                    emailDestino: $pedido->obterEmailDestino(),
                    criador: $pedido->obterUtilizadorAutenticado(),
                    expiraEm: $pedido->obterExpiracao(),
                );

        $convite =
            $resultado->obterConvite();

        $ligacaoConvite =
            route(
                'convites.aceitar',
                [
                    'codigoConvite' => $resultado->obterCodigo(),
                ],
            );

        return response()
            ->view(
                'convites.criado',
                [
                    'convite' => $convite,

                    'ligacaoConvite' => $ligacaoConvite,
                ],
            )
            ->header(
                'Cache-Control',
                'no-store, private',
            )
            ->header(
                'Pragma',
                'no-cache',
            )
            ->header(
                'Referrer-Policy',
                'no-referrer',
            )
            ->header(
                'X-Robots-Tag',
                'noindex, nofollow, noarchive',
            );
    }

    /**
     * Revoga um convite.
     *
     * Convites expirados também podem ser revogados. Uma repetição preserva a
     * primeira auditoria e devolve uma mensagem idempotente.
     *
     * @param  RevogarConviteRequest  $pedido  Pedido validado.
     * @param  Convite  $convite  Convite afetado.
     * @return RedirectResponse Redirecionamento para a listagem.
     *
     * @throws Throwable Quando ocorre um erro técnico inesperado.
     *
     * @since 2.0.0
     */
    public function revogar(
        RevogarConviteRequest $pedido,
        Convite $convite,
    ): RedirectResponse {
        try {
            $conviteRevogado =
                $this
                    ->servicoConvites
                    ->revogar(
                        convite: $convite,
                        responsavel: $pedido->obterUtilizadorAutenticado(),
                    );
        } catch (DomainException $excecao) {
            return to_route(
                'convites.indice',
            )->with(
                'erro',
                $excecao->getMessage(),
            );
        }

        if (
            ! $conviteRevogado->wasChanged([
                'revogado_em',
                'revogado_por_id',
            ])
        ) {
            return to_route(
                'convites.indice',
            )->with(
                'informacao',
                sprintf(
                    'O convite de %s já se encontrava revogado.',
                    $conviteRevogado->nome_convidado,
                ),
            );
        }

        return to_route(
            'convites.indice',
        )->with(
            'sucesso',
            sprintf(
                'O convite de %s foi revogado com sucesso.',
                $conviteRevogado->nome_convidado,
            ),
        );
    }

    /**
     * Normaliza o termo de pesquisa.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Pesquisa normalizada ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarPesquisa(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $pesquisa =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $valor,
                ),
            );

        if (
            ! is_string($pesquisa)
            || $pesquisa === ''
        ) {
            return null;
        }

        return mb_substr(
            $pesquisa,
            0,
            self::COMPRIMENTO_MAXIMO_PESQUISA,
        );
    }

    /**
     * Normaliza o estado recebido.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Estado reconhecido ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarEstado(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $estado =
            mb_strtolower(
                trim(
                    $valor,
                ),
            );

        return isset(
            self::ESTADOS_DISPONIVEIS[$estado],
        )
            ? $estado
            : null;
    }
}
