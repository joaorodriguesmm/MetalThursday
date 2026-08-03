<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gere a consulta administrativa dos utilizadores.
 *
 * A listagem permite pesquisar pelo nome ou endereço de e-mail e filtrar pelo
 * papel e pelo estado atual do acesso. A página de detalhes apresenta os
 * dados administrativos, o convite utilizado e o histórico do acesso.
 *
 * @since 2.0.0
 *
 * @version 2.0.1
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
     * @version 1.0.1
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
            ],
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
