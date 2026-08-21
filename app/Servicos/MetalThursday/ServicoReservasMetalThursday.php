<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Gere a criação e a seleção das reservas de MetalThursday.
 *
 * O histórico de nomeações é constituído exclusivamente pelas reservas
 * efetivamente atribuídas. O campo legado `proximo_nomeado_id` das
 * MetalThursdays não participa na seleção.
 *
 * @since 2.0.0
 */
final class ServicoReservasMetalThursday
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Cria a reserva correspondente à quinta-feira seguinte.
     *
     * Este método constitui o ponto de entrada do agendamento semanal
     * executado à sexta-feira.
     *
     * @param  CarbonInterface|null  $referencia  Momento de referência.
     * @return ReservaMetalThursday Reserva criada ou previamente existente.
     *
     * @since 2.0.0
     */
    public function criarReservaSemanal(
        ?CarbonInterface $referencia = null,
    ): ReservaMetalThursday {
        $momentoReferencia = CarbonImmutable::instance(
            $referencia
                ?? now(),
        );

        $data = $momentoReferencia
            ->next(
                CarbonImmutable::THURSDAY,
            )
            ->startOfDay();

        return $this->criarReservaAutomatica(
            $data,
        );
    }

    /**
     * Cria uma reserva automática para uma quinta-feira concreta.
     *
     * A operação é idempotente: se o slot já existir, é devolvida a reserva
     * existente sem alterar o respetivo responsável.
     *
     * Quando não existe nenhum utilizador elegível, o slot é criado sem
     * responsável para que possa ser posteriormente tratado por um
     * administrador.
     *
     * @param  CarbonInterface  $data  Quinta-feira reservada.
     * @return ReservaMetalThursday Reserva criada ou existente.
     *
     * @throws InvalidArgumentException Quando a data não é uma quinta-feira.
     *
     * @since 2.0.0
     */
    public function criarReservaAutomatica(
        CarbonInterface $data,
    ): ReservaMetalThursday {
        $dataNormalizada = CarbonImmutable::instance(
            $data,
        )->startOfDay();

        if (! $dataNormalizada->isThursday()) {
            throw new InvalidArgumentException(
                'A data da reserva tem de corresponder a uma quinta-feira.',
            );
        }

        $dataPersistivel =
            $dataNormalizada->toDateString();

        try {
            return DB::transaction(
                function () use (
                    $dataNormalizada,
                    $dataPersistivel,
                ): ReservaMetalThursday {
                    $reservaExistente = ReservaMetalThursday::query()
                        ->where(
                            'data',
                            $dataPersistivel,
                        )
                        ->lockForUpdate()
                        ->first();

                    if (
                        $reservaExistente
                        instanceof ReservaMetalThursday
                    ) {
                        return $reservaExistente;
                    }

                    Utilizador::query()
                        ->elegiveisParaNomeacao()
                        ->select([
                            'utilizadores.id',
                        ])
                        ->reorder(
                            'utilizadores.id',
                        )
                        ->lockForUpdate()
                        ->get();

                    $responsavel =
                        $this->obterUtilizadorHaMaisTempoSemNomeacao();

                    $reserva =
                        new ReservaMetalThursday;

                    $reserva->data =
                        $dataNormalizada;

                    if ($responsavel instanceof Utilizador) {
                        $reserva
                            ->responsavel()
                            ->associate(
                                $responsavel,
                            );
                    }

                    $reserva->saveOrFail();

                    return $reserva->refresh();
                },
                self::TENTATIVAS_TRANSACAO,
            );
        } catch (
            UniqueConstraintViolationException $excecao
        ) {
            $reservaExistente = ReservaMetalThursday::query()
                ->where(
                    'data',
                    $dataPersistivel,
                )
                ->first();

            if (
                $reservaExistente
                instanceof ReservaMetalThursday
            ) {
                return $reservaExistente;
            }

            throw $excecao;
        }
    }

    /**
     * Obtém o utilizador elegível há mais tempo sem ser nomeado.
     *
     * Utilizadores sem qualquer reserva atribuída têm prioridade. Nos
     * restantes casos, é considerada a data da reserva mais recente.
     * Empates são resolvidos pelo nome e depois pelo identificador.
     *
     * Apenas `reservas_metal_thursday` constitui histórico de nomeações.
     *
     * @param  int|null  $identificadorExcluido  Utilizador a ignorar.
     * @return Utilizador|null Utilizador encontrado ou nulo.
     *
     * @since 2.0.0
     */
    public function obterUtilizadorHaMaisTempoSemNomeacao(
        ?int $identificadorExcluido = null,
    ): ?Utilizador {
        $ultimasNomeacoes = ReservaMetalThursday::query()
            ->selectRaw(
                'responsavel_id, MAX(data) AS ultima_nomeacao_em',
            )
            ->whereNotNull(
                'responsavel_id',
            )
            ->groupBy(
                'responsavel_id',
            );

        return Utilizador::query()
            ->elegiveisParaNomeacao()
            ->when(
                $identificadorExcluido !== null
                    && $identificadorExcluido > 0,
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->where(
                    'utilizadores.id',
                    '!=',
                    $identificadorExcluido,
                ),
            )
            ->leftJoinSub(
                $ultimasNomeacoes,
                'ultimas_nomeacoes',
                static function (
                    JoinClause $juncao,
                ): void {
                    $juncao->on(
                        'utilizadores.id',
                        '=',
                        'ultimas_nomeacoes.responsavel_id',
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
                'utilizadores.*',
            ])
            ->first();
    }

    /**
     * Obtém a reserva pendente mais antiga de um utilizador.
     *
     * A ordenação mantém um resultado determinístico mesmo perante uma
     * eventual inconsistência histórica com mais de uma reserva pendente.
     *
     * @param  Utilizador  $utilizador  Utilizador responsável.
     * @return ReservaMetalThursday|null Reserva encontrada ou nulo.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function obterReservaPendenteDoUtilizador(
        Utilizador $utilizador,
    ): ?ReservaMetalThursday {
        $identificador =
            $utilizador->getKey();

        if (
            ! $utilizador->exists
            || ! is_numeric(
                $identificador,
            )
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                'O utilizador da reserva deve estar persistido.',
            );
        }

        return ReservaMetalThursday::query()
            ->where(
                'responsavel_id',
                (int) $identificador,
            )
            ->whereNull(
                'metal_thursday_id',
            )
            ->orderBy(
                'data',
            )
            ->orderBy(
                'id',
            )
            ->first();
    }
}
