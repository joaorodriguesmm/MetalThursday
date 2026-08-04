<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AlterarPapelUtilizadorRequest;
use App\Http\Requests\Utilizadores\EncerrarSessoesUtilizadorRequest;
use App\Http\Requests\Utilizadores\ReativarUtilizadorRequest;
use App\Http\Requests\Utilizadores\SuspenderUtilizadorRequest;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Utilizadores\ServicoAcessoUtilizadores;
use App\Servicos\Utilizadores\ServicoPapeisUtilizadores;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Gere a consulta e as operações administrativas dos utilizadores.
 *
 * A listagem permite pesquisar pelo nome ou endereço de e-mail e filtrar pelo
 * papel e pelo estado atual do acesso. A página de detalhes apresenta os
 * dados administrativos, o convite utilizado e os históricos do acesso e dos
 * papéis.
 *
 * A suspensão, a reativação, o encerramento das sessões e a alteração do
 * papel são delegados aos respetivos serviços transacionais.
 *
 * @since 2.0.0
 *
 * @version 5.0.0
 */
final class ControladorUtilizador extends Controller
{
    use AuthorizesRequests;

    /**
     * Número de utilizadores apresentados por página.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const REGISTOS_POR_PAGINA =
        20;

    /**
     * Comprimento máximo do termo de pesquisa.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA =
        100;

    /**
     * Identificador público do estado de acesso ativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ESTADO_ATIVO =
        'ativo';

    /**
     * Identificador público do estado de acesso suspenso.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ESTADO_SUSPENSO =
        'suspenso';

    /**
     * Cria o controlador.
     *
     * @param  ServicoAcessoUtilizadores  $servicoAcessoUtilizadores  Serviço
     *                                                                de
     *                                                                gestão
     *                                                                do
     *                                                                acesso.
     * @param  ServicoPapeisUtilizadores  $servicoPapeisUtilizadores  Serviço
     *                                                                de
     *                                                                gestão
     *                                                                dos
     *                                                                papéis.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        private readonly ServicoAcessoUtilizadores $servicoAcessoUtilizadores,
        private readonly ServicoPapeisUtilizadores $servicoPapeisUtilizadores,
    ) {}

    /**
     * Apresenta a lista paginada dos utilizadores.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem administrativa dos utilizadores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function indice(
        Request $pedido,
    ): View {
        $this->authorize(
            'viewAny',
            Utilizador::class,
        );

        $pesquisa =
            $this->normalizarPesquisa(
                $pedido->query(
                    'pesquisa',
                ),
            );

        $papel =
            PapelUtilizador::tentarCriar(
                $pedido->query(
                    'papel',
                ),
            );

        $estado =
            $this->normalizarEstado(
                $pedido->query(
                    'estado',
                ),
            );

        $utilizadores =
            Utilizador::query()
                ->select([
                    'id',
                    'nome',
                    'email',
                    'email_verified_at',
                    'fotografia',
                    'papel',
                    'suspenso_em',
                    'created_at',
                ])
                ->when(
                    $pesquisa !== null,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->where(
                        static function (
                            Builder $construtor,
                        ) use (
                            $pesquisa,
                        ): void {
                            $construtor
                                ->where(
                                    'nome',
                                    'like',
                                    '%'.$pesquisa.'%',
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    '%'.$pesquisa.'%',
                                );
                        },
                    ),
                )
                ->when(
                    $papel !== null,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->where(
                        'papel',
                        $papel->value,
                    ),
                )
                ->when(
                    $estado === self::ESTADO_ATIVO,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->whereNull(
                        'suspenso_em',
                    ),
                )
                ->when(
                    $estado === self::ESTADO_SUSPENSO,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->whereNotNull(
                        'suspenso_em',
                    ),
                )
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->paginate(
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'utilizadores.indice',
            [
                'utilizadores' => $utilizadores,

                'pesquisaAtual' => $pesquisa,

                'papelAtual' => $papel,

                'estadoAtual' => $estado,

                'papeisDisponiveis' => PapelUtilizador::cases(),

                'estadosDisponiveis' => [
                    self::ESTADO_ATIVO => 'Ativo',

                    self::ESTADO_SUSPENSO => 'Suspenso',
                ],

                'filtrosAtivos' => $pesquisa !== null
                    || $papel !== null
                    || $estado !== null,
            ],
        );
    }

    /**
     * Apresenta os detalhes administrativos de um utilizador.
     *
     * Todas as relações utilizadas pela vista são carregadas explicitamente
     * para impedir consultas preguiçosas durante a apresentação.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return View Página de detalhes do utilizador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function detalhes(
        Utilizador $utilizador,
    ): View {
        $this->authorize(
            'view',
            $utilizador,
        );

        $utilizador->load([
            'responsavelSuspensao:id,nome,email',

            'registosAcesso' => static function (
                HasMany $relacao,
            ): void {
                $relacao
                    ->select([
                        'id',
                        'utilizador_id',
                        'acao',
                        'motivo',
                        'responsavel_id',
                        'registado_em',
                    ])
                    ->with([
                        'responsavel:id,nome,email',
                    ]);
            },

            'registosPapel' => static function (
                HasMany $relacao,
            ): void {
                $relacao
                    ->select([
                        'id',
                        'utilizador_id',
                        'papel_anterior',
                        'papel_novo',
                        'responsavel_id',
                        'registado_em',
                    ])
                    ->with([
                        'responsavel:id,nome,email',
                    ]);
            },

            'conviteUtilizado' => static function (
                HasOne $relacao,
            ): void {
                $relacao
                    ->select([
                        'id',
                        'nome_convidado',
                        'email_destino',
                        'criado_por_id',
                        'utilizado_por_id',
                        'utilizado_em',
                        'created_at',
                    ])
                    ->with([
                        'criador:id,nome,email',
                    ]);
            },
        ]);

        return view(
            'utilizadores.detalhes',
            [
                'utilizador' => $utilizador,

                'papeisDisponiveis' => PapelUtilizador::cases(),
            ],
        );
    }

    /**
     * Suspende o acesso de um utilizador.
     *
     * O pedido fornece o responsável e o motivo já autorizados e validados.
     * O estado atual, o histórico, o token persistente e as sessões são
     * alterados atomicamente pelo serviço.
     *
     * @param  SuspenderUtilizadorRequest  $pedido  Pedido validado.
     * @param  Utilizador  $utilizador  Utilizador a suspender.
     * @return RedirectResponse Redirecionamento para os detalhes.
     *
     * @throws Throwable Quando ocorre um erro técnico inesperado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function suspender(
        SuspenderUtilizadorRequest $pedido,
        Utilizador $utilizador,
    ): RedirectResponse {
        $responsavel =
            $pedido->obterUtilizadorAutenticado();

        $motivo =
            $pedido->obterMotivo();

        try {
            $this
                ->servicoAcessoUtilizadores
                ->suspender(
                    utilizador: $utilizador,
                    responsavel: $responsavel,
                    motivo: $motivo,
                );
        } catch (DomainException $excecao) {
            return to_route(
                'utilizadores.detalhes',
                $utilizador,
            )
                ->withInput([
                    'motivo' => $motivo,
                ])
                ->withErrors(
                    [
                        'motivo' => $excecao->getMessage(),
                    ],
                    'suspensao',
                );
        }

        return to_route(
            'utilizadores.detalhes',
            $utilizador,
        )->with(
            'sucesso',
            sprintf(
                'O acesso de %s foi suspenso com sucesso.',
                $utilizador->nome,
            ),
        );
    }

    /**
     * Reativa o acesso de um utilizador.
     *
     * A reativação limpa o estado atual de suspensão, cria o respetivo
     * histórico, renova o token persistente e elimina sessões concorrentes.
     *
     * @param  ReativarUtilizadorRequest  $pedido  Pedido validado.
     * @param  Utilizador  $utilizador  Utilizador a reativar.
     * @return RedirectResponse Redirecionamento para os detalhes.
     *
     * @throws Throwable Quando ocorre um erro técnico inesperado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function reativar(
        ReativarUtilizadorRequest $pedido,
        Utilizador $utilizador,
    ): RedirectResponse {
        $responsavel =
            $pedido->obterUtilizadorAutenticado();

        try {
            $this
                ->servicoAcessoUtilizadores
                ->reativar(
                    utilizador: $utilizador,
                    responsavel: $responsavel,
                );
        } catch (DomainException $excecao) {
            return to_route(
                'utilizadores.detalhes',
                $utilizador,
            )->withErrors(
                [
                    'confirmar_reativacao' => $excecao->getMessage(),
                ],
                'reativacao',
            );
        }

        return to_route(
            'utilizadores.detalhes',
            $utilizador,
        )->with(
            'sucesso',
            sprintf(
                'O acesso de %s foi reativado com sucesso.',
                $utilizador->nome,
            ),
        );
    }

    /**
     * Encerra todas as sessões persistidas de um utilizador.
     *
     * A operação pode ser aplicada a utilizadores ativos ou suspensos. O
     * serviço renova também o token persistente, impedindo autenticações
     * futuras baseadas na credencial anterior.
     *
     * @param  EncerrarSessoesUtilizadorRequest  $pedido  Pedido validado.
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @return RedirectResponse Redirecionamento para os detalhes.
     *
     * @throws Throwable Quando ocorre um erro técnico inesperado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function encerrarSessoes(
        EncerrarSessoesUtilizadorRequest $pedido,
        Utilizador $utilizador,
    ): RedirectResponse {
        $responsavel =
            $pedido->obterUtilizadorAutenticado();

        try {
            $numeroSessoesEncerradas =
                $this
                    ->servicoAcessoUtilizadores
                    ->encerrarSessoes(
                        utilizador: $utilizador,
                        responsavel: $responsavel,
                    );
        } catch (DomainException $excecao) {
            return to_route(
                'utilizadores.detalhes',
                $utilizador,
            )->withErrors(
                [
                    'confirmar_encerramento_sessoes' => $excecao->getMessage(),
                ],
                'sessoes',
            );
        }

        $mensagem =
            match ($numeroSessoesEncerradas) {
                0 => sprintf(
                    'As autenticações persistentes de %s foram invalidadas. Não existiam sessões ativas para encerrar.',
                    $utilizador->nome,
                ),

                1 => sprintf(
                    'Foi encerrada 1 sessão de %s e as autenticações persistentes foram invalidadas.',
                    $utilizador->nome,
                ),

                default => sprintf(
                    'Foram encerradas %d sessões de %s e as autenticações persistentes foram invalidadas.',
                    $numeroSessoesEncerradas,
                    $utilizador->nome,
                ),
            };

        return to_route(
            'utilizadores.detalhes',
            $utilizador,
        )->with(
            'sucesso',
            $mensagem,
        );
    }

    /**
     * Altera o papel de um utilizador.
     *
     * O serviço altera o papel e o histórico, renova o token persistente e
     * encerra todas as sessões do utilizador afetado na mesma transação.
     *
     * @param  AlterarPapelUtilizadorRequest  $pedido  Pedido validado.
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @return RedirectResponse Redirecionamento para os detalhes.
     *
     * @throws Throwable Quando ocorre um erro técnico inesperado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function alterarPapel(
        AlterarPapelUtilizadorRequest $pedido,
        Utilizador $utilizador,
    ): RedirectResponse {
        $responsavel =
            $pedido->obterUtilizadorAutenticado();

        $papelNovo =
            $pedido->obterPapelNovo();

        try {
            $this
                ->servicoPapeisUtilizadores
                ->alterar(
                    utilizador: $utilizador,
                    responsavel: $responsavel,
                    papelNovo: $papelNovo,
                );
        } catch (DomainException $excecao) {
            return to_route(
                'utilizadores.detalhes',
                $utilizador,
            )
                ->withInput([
                    'papel' => $papelNovo->value,
                ])
                ->withErrors(
                    [
                        'papel' => $excecao->getMessage(),
                    ],
                    'papel',
                );
        }

        return to_route(
            'utilizadores.detalhes',
            $utilizador,
        )->with(
            'sucesso',
            sprintf(
                'O papel de %s foi alterado para %s com sucesso.',
                $utilizador->nome,
                $papelNovo->etiqueta(),
            ),
        );
    }

    /**
     * Normaliza o termo de pesquisa recebido.
     *
     * Espaços exteriores são removidos e sequências interiores de espaços
     * são reduzidas a um único espaço.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Pesquisa normalizada ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * Normaliza o estado de acesso recebido.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Estado reconhecido ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

        return match ($estado) {
            self::ESTADO_ATIVO,
            self::ESTADO_SUSPENSO => $estado,

            default => null,
        };
    }
}
