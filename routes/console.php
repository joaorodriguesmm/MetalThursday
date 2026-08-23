<?php

declare(strict_types=1);

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoUtilizadorNomeado;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Illuminate\Support\Facades\Schedule;

/**
 * Define os comandos e tarefas agendadas da aplicação.
 *
 * @since 1.0.0
 */
Schedule::call(
    static function (): void {
        $servicoReservas =
            app(
                ServicoReservasMetalThursday::class,
            );

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
    },
)
    ->name(
        'reservas-metal-thursday:criar-semanal',
    )
    ->weeklyOn(
        5,
        '00:00',
    )
    ->timezone(
        (string) config(
            'app.timezone',
        ),
    )
    ->withoutOverlapping();
