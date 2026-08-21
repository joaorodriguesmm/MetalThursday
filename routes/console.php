<?php

declare(strict_types=1);

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

        $servicoReservas->criarReservaSemanal();
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
