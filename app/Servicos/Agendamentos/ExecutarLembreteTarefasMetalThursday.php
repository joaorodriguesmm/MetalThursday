<?php

declare(strict_types=1);

namespace App\Servicos\Agendamentos;

use App\Servicos\Notificacoes\NotificadorLembretesMetalThursday;
use Throwable;

/**
 * Executa o envio agendado dos lembretes das tarefas de MetalThursday que
 * devem ser concluídas no próprio dia.
 *
 * @since 2.0.0
 */
final class ExecutarLembreteTarefasMetalThursday
{
    /**
     * Executa o envio dos lembretes do próprio dia.
     *
     * O serviço é resolvido pelo contentor apenas quando a tarefa é executada.
     * Assim, substituições realizadas no contentor, incluindo fakes utilizados
     * nos testes, são respeitadas.
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
                ->notificarTarefasDoDia();
        } catch (Throwable $excecao) {
            report(
                $excecao,
            );
        }
    }
}
