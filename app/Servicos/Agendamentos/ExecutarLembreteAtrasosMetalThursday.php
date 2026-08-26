<?php

declare(strict_types=1);

namespace App\Servicos\Agendamentos;

use App\Servicos\Notificacoes\NotificadorLembretesMetalThursday;
use Throwable;

/**
 * Executa o envio agendado dos lembretes das MetalThursdays em atraso.
 *
 * @since 2.0.0
 */
final class ExecutarLembreteAtrasosMetalThursday
{
    /**
     * Executa o envio dos lembretes dos atrasos existentes.
     *
     * O serviço é resolvido pelo contentor apenas no momento da execução da
     * tarefa, respeitando substituições realizadas no contentor.
     *
     * Falhas técnicas são reportadas e ficam isoladas na fronteira da tarefa
     * agendada.
     *
     * @param  NotificadorLembretesMetalThursday  $notificadorLembretes  Serviço
     *                                                                   de
     *                                                                   lembretes.
     *
     * @since 2.0.0
     */
    public function __invoke(
        NotificadorLembretesMetalThursday $notificadorLembretes,
    ): void {
        try {
            $notificadorLembretes
                ->notificarAtrasos();
        } catch (Throwable $excecao) {
            report(
                $excecao,
            );
        }
    }
}
