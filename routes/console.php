<?php

declare(strict_types=1);

use App\Servicos\Agendamentos\ExecutarLembreteAtrasosMetalThursday;
use App\Servicos\Agendamentos\ExecutarLembreteTarefasMetalThursday;
use App\Servicos\Agendamentos\ExecutarReservaSemanalMetalThursday;
use Illuminate\Support\Facades\Schedule;

/**
 * Define os comandos e tarefas agendadas da aplicação.
 *
 * @since 1.0.0
 */
$fusoHorario =
    (string) config(
        'app.timezone',
    );

Schedule::call(
    new ExecutarReservaSemanalMetalThursday,
)
    ->name(
        'reservas-metal-thursday:criar-semanal',
    )
    ->weeklyOn(
        5,
        '00:00',
    )
    ->timezone(
        $fusoHorario,
    )
    ->withoutOverlapping();

Schedule::call(
    new ExecutarLembreteTarefasMetalThursday,
)
    ->name(
        'lembretes-metal-thursday:tarefas-do-dia',
    )
    ->dailyAt(
        '08:00',
    )
    ->timezone(
        $fusoHorario,
    )
    ->withoutOverlapping();

Schedule::call(
    new ExecutarLembreteAtrasosMetalThursday,
)
    ->name(
        'lembretes-metal-thursday:atrasos',
    )
    ->dailyAt(
        '08:05',
    )
    ->timezone(
        $fusoHorario,
    )
    ->withoutOverlapping();
