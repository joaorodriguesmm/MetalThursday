<?php

declare(strict_types=1);

namespace App\Servicos\Agendamentos;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoUtilizadorNomeado;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Throwable;

/**
 * Executa a criação semanal de uma reserva de MetalThursday e notifica o
 * responsável efetivamente atribuído.
 *
 * A classe concentra a orquestração da tarefa agendada, mantendo a definição
 * do calendário independente das regras de domínio e das notificações.
 *
 * @since 2.0.0
 */
final class ExecutarReservaSemanalMetalThursday
{
    /**
     * Executa a criação semanal e notifica o responsável quando foi criada
     * uma nova reserva.
     *
     * A dependência é resolvida pelo contentor no momento da execução da
     * tarefa, evitando manter serviços previamente resolvidos no calendário.
     *
     * Uma falha no envio da notificação é reportada sem reverter a reserva já
     * criada.
     *
     * @param  ServicoReservasMetalThursday  $servicoReservas  Serviço
     *                                                         responsável
     *                                                         pelas reservas.
     *
     * @since 2.0.0
     */
    public function __invoke(
        ServicoReservasMetalThursday $servicoReservas,
    ): void {
        $reserva =
            $servicoReservas->criarReservaSemanal();

        if (! $reserva instanceof ReservaMetalThursday) {
            return;
        }

        $reserva->loadMissing([
            'responsavel.permissoesEmail',
        ]);

        $responsavel =
            $reserva->responsavel;

        if (! $responsavel instanceof Utilizador) {
            return;
        }

        try {
            $responsavel->notify(
                new NotificacaoUtilizadorNomeado(
                    $reserva,
                ),
            );
        } catch (Throwable $excecao) {
            report(
                $excecao,
            );
        }
    }
}
