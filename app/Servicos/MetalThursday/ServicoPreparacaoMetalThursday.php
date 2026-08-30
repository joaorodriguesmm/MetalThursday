<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Resultados\MetalThursday\MetalThursdayCriada;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Gere o ciclo de preparação de uma MetalThursday através de uma reserva.
 *
 * O serviço é responsável pela persistência transacional dos rascunhos e pela
 * coordenação da respetiva finalização. A persistência definitiva da
 * MetalThursday e das secções continua delegada no serviço específico dessa
 * responsabilidade.
 *
 * @since 2.0.0
 */
final class ServicoPreparacaoMetalThursday
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Cria o serviço com a persistência definitiva necessária à finalização.
     *
     * @param  ServicoPersistenciaMetalThursday  $servicoPersistencia  Serviço
     *                                                                 responsável
     *                                                                 pela
     *                                                                 persistência
     *                                                                 final.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoPersistenciaMetalThursday $servicoPersistencia,
    ) {}

    /**
     * Guarda ou atualiza o único rascunho de uma reserva.
     *
     * A reserva é novamente obtida e bloqueada dentro da transação. Assim,
     * uma submissão concorrente não consegue criar um rascunho depois de a
     * reserva ter sido entretanto cumprida.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva preparada.
     * @param  Utilizador  $responsavel  Responsável autenticado.
     * @param  array<string, mixed>  $dados  Dados validados do rascunho.
     * @return RascunhoMetalThursday|null Rascunho persistido ou nulo quando a
     *                                    reserva deixou de estar disponível.
     *
     * @throws InvalidArgumentException Quando os modelos recebidos não possuem
     *                                  identificadores persistidos válidos.
     *
     * @since 2.0.0
     */
    public function guardarRascunho(
        ReservaMetalThursday $reserva,
        Utilizador $responsavel,
        array $dados,
    ): ?RascunhoMetalThursday {
        $identificadorReserva =
            $reserva->getKey();

        $identificadorResponsavel =
            $responsavel->getKey();

        if (
            ! is_numeric($identificadorReserva)
            || (int) $identificadorReserva < 1
        ) {
            throw new InvalidArgumentException(
                'A reserva deve possuir um identificador persistido válido.',
            );
        }

        if (
            ! is_numeric($identificadorResponsavel)
            || (int) $identificadorResponsavel < 1
        ) {
            throw new InvalidArgumentException(
                'O responsável deve possuir um identificador persistido válido.',
            );
        }

        return DB::transaction(
            function () use (
                $identificadorReserva,
                $identificadorResponsavel,
                $dados,
            ): ?RascunhoMetalThursday {
                $reservaBloqueada =
                    ReservaMetalThursday::query()
                        ->whereKey(
                            (int) $identificadorReserva,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $reservaBloqueada
                        instanceof ReservaMetalThursday
                    || ! $reservaBloqueada->estaPendente()
                    || ! is_numeric(
                        $reservaBloqueada->responsavel_id,
                    )
                    || (int) $reservaBloqueada->responsavel_id
                    !== (int) $identificadorResponsavel
                ) {
                    return null;
                }

                $rascunho =
                    RascunhoMetalThursday::query()
                        ->where(
                            'reserva_metal_thursday_id',
                            $reservaBloqueada->getKey(),
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $rascunho instanceof RascunhoMetalThursday) {
                    $rascunho =
                        new RascunhoMetalThursday;

                    $rascunho
                        ->reservaMetalThursday()
                        ->associate(
                            $reservaBloqueada,
                        );
                }

                $rascunho->dados =
                    $dados;

                $rascunho->saveOrFail();

                return $rascunho->refresh();
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Finaliza a preparação de uma reserva.
     *
     * A reserva é novamente bloqueada antes da persistência definitiva. Apenas
     * depois de a MetalThursday, as secções e o encadeamento das reservas terem
     * sido persistidos com sucesso é eliminado o eventual rascunho.
     *
     * Toda a operação pertence à mesma transação exterior. Assim, uma falha em
     * qualquer etapa preserva a reserva pendente e o rascunho existente.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva preparada.
     * @param  Utilizador  $responsavel  Responsável autenticado.
     * @param  array<string, mixed>  $dados  Dados definitivos validados.
     * @return MetalThursdayCriada|null Resultado da criação ou nulo quando a
     *                                  reserva deixou de estar disponível.
     *
     * @throws InvalidArgumentException Quando os modelos recebidos não possuem
     *                                  identificadores persistidos válidos.
     *
     * @since 2.0.0
     */
    public function finalizar(
        ReservaMetalThursday $reserva,
        Utilizador $responsavel,
        array $dados,
    ): ?MetalThursdayCriada {
        $identificadorReserva =
            $reserva->getKey();

        $identificadorResponsavel =
            $responsavel->getKey();

        if (
            ! is_numeric($identificadorReserva)
            || (int) $identificadorReserva < 1
        ) {
            throw new InvalidArgumentException(
                'A reserva deve possuir um identificador persistido válido.',
            );
        }

        if (
            ! is_numeric($identificadorResponsavel)
            || (int) $identificadorResponsavel < 1
        ) {
            throw new InvalidArgumentException(
                'O responsável deve possuir um identificador persistido válido.',
            );
        }

        return DB::transaction(
            function () use (
                $identificadorReserva,
                $identificadorResponsavel,
                $dados,
            ): ?MetalThursdayCriada {
                $reservaBloqueada =
                    ReservaMetalThursday::query()
                        ->whereKey(
                            (int) $identificadorReserva,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $reservaBloqueada instanceof ReservaMetalThursday
                    || ! $reservaBloqueada->estaPendente()
                    || ! is_numeric(
                        $reservaBloqueada->responsavel_id,
                    )
                    || (int) $reservaBloqueada->responsavel_id
                    !== (int) $identificadorResponsavel
                ) {
                    return null;
                }

                $resultadoCriacao =
                    $this->servicoPersistencia
                        ->criarComResultado(
                            $dados,
                        );

                RascunhoMetalThursday::query()
                    ->where(
                        'reserva_metal_thursday_id',
                        $reservaBloqueada->getKey(),
                    )
                    ->delete();

                return $resultadoCriacao;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }
}
