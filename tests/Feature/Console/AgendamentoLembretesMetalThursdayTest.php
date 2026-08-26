<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteTarefaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelConsola;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração do agendamento diário dos lembretes de MetalThursday.
 *
 * @since 2.0.0
 */
final class AgendamentoLembretesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o agendamento diário envia o lembrete ao responsável por
     * uma reserva pendente do próprio dia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_diario_notifica_tarefa_pendente_do_proprio_dia(): void
    {
        Notification::fake();

        $responsavel = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoLembreteTarefas();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertSentTo(
            $responsavel,
            NotificacaoLembreteTarefaMetalThursday::class,
        );
    }

    /**
     * Confirma que o agendamento diário não envia um lembrete quando apenas
     * existem reservas de outros dias.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_diario_nao_notifica_reservas_de_outros_dias(): void
    {
        Notification::fake();

        $responsavelAnterior = Utilizador::factory()
            ->create();

        $responsavelSeguinte = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $responsavelAnterior,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-03',
                ),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoLembreteTarefas();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertNothingSent();
    }

    /**
     * Executa diretamente o evento diário registado em `routes/console.php`.
     *
     * @since 2.0.0
     */
    private function executarAgendamentoLembreteTarefas(): void
    {
        app(
            KernelConsola::class,
        )->bootstrap();

        $evento = collect(
            app(
                Schedule::class,
            )->events(),
        )->first(
            static fn (mixed $evento): bool => $evento instanceof CallbackEvent
                && $evento->description ===
                'lembretes-metal-thursday:tarefas-do-dia',
        );

        self::assertInstanceOf(
            CallbackEvent::class,
            $evento,
        );

        self::assertSame(
            '0 8 * * *',
            $evento->expression,
        );

        self::assertSame(
            (string) config(
                'app.timezone',
            ),
            $evento->timezone,
        );

        self::assertTrue(
            $evento->withoutOverlapping,
        );

        $evento->run(
            $this->app,
        );
    }
}
