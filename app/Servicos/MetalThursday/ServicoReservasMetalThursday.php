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
     * Executa o fallback semanal das reservas.
     *
     * É verificada a reserva da quinta-feira imediatamente anterior ao momento
     * de referência. Apenas quando essa reserva existe e continua pendente é
     * tentada a criação do slot da quinta-feira seguinte.
     *
     * Uma reserva anterior inexistente ou já cumprida não desencadeia qualquer
     * nova reserva.
     *
     * @param  CarbonInterface|null  $referencia  Momento de referência.
     * @return ReservaMetalThursday|null Reserva criada pelo fallback ou nulo
     *                                   quando nenhuma criação foi necessária.
     *
     * @since 2.0.0
     */
    public function criarReservaSemanal(
        ?CarbonInterface $referencia = null,
    ): ?ReservaMetalThursday {
        $momentoReferencia = CarbonImmutable::instance(
            $referencia
                ?? now(),
        );

        $dataAnterior = $momentoReferencia
            ->previous(
                CarbonImmutable::THURSDAY,
            )
            ->startOfDay();

        $dataSeguinte =
            $dataAnterior->addWeek();

        return DB::transaction(
            function () use (
                $dataAnterior,
                $dataSeguinte,
            ): ?ReservaMetalThursday {
                $reservaAnterior = ReservaMetalThursday::query()
                    ->where(
                        'data',
                        $dataAnterior->toDateString(),
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $reservaAnterior instanceof ReservaMetalThursday
                    || ! $reservaAnterior->estaPendente()
                ) {
                    return null;
                }

                return $this->criarReservaAutomatica(
                    $dataSeguinte,
                );
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Cria uma reserva automática para uma quinta-feira concreta.
     *
     * A operação é idempotente: se o slot já existir, nenhuma nova reserva é
     * criada e a operação devolve nulo sem alterar o respetivo responsável.
     *
     * Quando não existe nenhum utilizador elegível, o slot é criado sem
     * responsável para que possa ser posteriormente tratado por um
     * administrador.
     *
     * @param  CarbonInterface  $data  Quinta-feira reservada.
     * @return ReservaMetalThursday|null Reserva criada ou nulo quando o slot já
     *                                   existia.
     *
     * @throws InvalidArgumentException Quando a data não é uma quinta-feira.
     *
     * @since 2.0.0
     */
    public function criarReservaAutomatica(
        CarbonInterface $data,
    ): ?ReservaMetalThursday {
        $dataNormalizada =
            $this->normalizarDataReserva(
                $data,
            );

        $dataPersistivel =
            $dataNormalizada->toDateString();

        try {
            return DB::transaction(
                function () use (
                    $dataNormalizada,
                    $dataPersistivel,
                ): ?ReservaMetalThursday {
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
                        return null;
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
                return null;
            }

            throw $excecao;
        }
    }

    /**
     * Tenta criar uma reserva para um utilizador explicitamente nomeado.
     *
     * A operação nunca substitui uma reserva já existente para a data. Nesse
     * caso, devolve nulo sem alterar o responsável ou qualquer outro dado do
     * slot.
     *
     * A elegibilidade do nomeado é novamente confirmada dentro da transação,
     * depois de o utilizador ter sido bloqueado. Assim, a operação não depende
     * exclusivamente da validação anteriormente realizada pela camada HTTP.
     *
     * @param  CarbonInterface  $data  Quinta-feira reservada.
     * @param  int  $identificadorResponsavel  Utilizador nomeado.
     * @return ReservaMetalThursday|null Reserva criada ou nulo quando o slot
     *                                   já existia.
     *
     * @throws InvalidArgumentException Quando a data, o identificador ou a
     *                                  elegibilidade não são válidos.
     *
     * @since 2.0.0
     */
    public function criarReservaParaNomeado(
        CarbonInterface $data,
        int $identificadorResponsavel,
    ): ?ReservaMetalThursday {
        $dataNormalizada =
            $this->normalizarDataReserva(
                $data,
            );

        if ($identificadorResponsavel < 1) {
            throw new InvalidArgumentException(
                'O utilizador nomeado deve possuir um identificador válido.',
            );
        }

        $dataPersistivel =
            $dataNormalizada->toDateString();

        try {
            return DB::transaction(
                function () use (
                    $dataNormalizada,
                    $dataPersistivel,
                    $identificadorResponsavel,
                ): ?ReservaMetalThursday {
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
                        return null;
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

                    $responsavel = Utilizador::query()
                        ->whereKey(
                            $identificadorResponsavel,
                        )
                        ->first();

                    if (! $responsavel instanceof Utilizador) {
                        throw new InvalidArgumentException(
                            'O utilizador nomeado não existe.',
                        );
                    }

                    $estaElegivel = Utilizador::query()
                        ->elegiveisParaNomeacao()
                        ->whereKey(
                            $identificadorResponsavel,
                        )
                        ->exists();

                    if (! $estaElegivel) {
                        throw new InvalidArgumentException(
                            'O utilizador nomeado não está disponível para uma nova nomeação.',
                        );
                    }

                    $reserva =
                        new ReservaMetalThursday;

                    $reserva->data =
                        $dataNormalizada;

                    $reserva
                        ->responsavel()
                        ->associate(
                            $responsavel,
                        );

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
                return null;
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

    /**
     * Normaliza e valida a data de uma reserva.
     *
     * @param  CarbonInterface  $data  Data recebida.
     * @return CarbonImmutable Data normalizada para o início do dia.
     *
     * @throws InvalidArgumentException Quando a data não é uma quinta-feira.
     *
     * @since 2.0.0
     */
    private function normalizarDataReserva(
        CarbonInterface $data,
    ): CarbonImmutable {
        $dataNormalizada = CarbonImmutable::instance(
            $data,
        )->startOfDay();

        if (! $dataNormalizada->isThursday()) {
            throw new InvalidArgumentException(
                'A data da reserva tem de corresponder a uma quinta-feira.',
            );
        }

        return $dataNormalizada;
    }
}
