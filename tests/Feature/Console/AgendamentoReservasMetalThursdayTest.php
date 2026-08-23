<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelConsola;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração do agendamento semanal das reservas de MetalThursday.
 *
 * @since 2.0.0
 */
final class AgendamentoReservasMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o agendamento cria o fallback e notifica apenas o
     * responsável efetivamente atribuído.
     *
     * @since 2.0.0
     */
    #[Test]
    public function fallback_agendado_notifica_responsavel_atribuido(): void
    {
        Notification::fake();

        $responsavelAnterior = Utilizador::factory()
            ->create([
                'nome' => 'Responsável anterior',
            ]);

        $nomeado = Utilizador::factory()
            ->create([
                'nome' => 'Nomeado automático',
            ]);

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

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-21 00:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoSemanal();
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'data' => '2026-08-27',

                'responsavel_id' => $nomeado->getKey(),

                'metal_thursday_id' => null,
            ],
        );

        Notification::assertSentTo(
            $nomeado,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertNotSentTo(
            $responsavelAnterior,
            NotificacaoUtilizadorNomeado::class,
        );
    }

    /**
     * Confirma que o agendamento não envia nova nomeação quando o slot seguinte
     * já existia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function fallback_agendado_nao_notifica_quando_slot_ja_existe(): void
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
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-21 00:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoSemanal();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertNothingSent();

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            2,
        );
    }

    /**
     * Executa diretamente o evento semanal registado em `routes/console.php`.
     *
     * @since 2.0.0
     */
    private function executarAgendamentoSemanal(): void
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
                'reservas-metal-thursday:criar-semanal',
        );

        self::assertInstanceOf(
            CallbackEvent::class,
            $evento,
        );

        $evento->run(
            $this->app,
        );
    }
}
